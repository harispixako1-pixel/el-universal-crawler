<?php

namespace App\Services\Crawler;

use App\Contracts\HtmlFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ScrapingBeeClient implements HtmlFetcher
{
    private const API_ENDPOINT = 'https://app.scrapingbee.com/api/v1';

    public function __construct(
        private readonly string $apiKey,
        private readonly Client $client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
        ])
    ) {
        if (trim($this->apiKey) === '') {
            throw new RuntimeException('ScrapingBee API key is required.');
        }
    }

    public function get(string $url, array $params = []): string
    {
        return $this->fetch($url, $params);
    }

    public function fetch(string $url, array $params = []): string
    {
        try {
            $query = array_merge([
                'url' => $url,
                'mode' => 'auto',
            ], $params);

            foreach (['render_js', 'premium_proxy', 'stealth_proxy', 'transparent_status_code'] as $conflictingParam) {
                unset($query[$conflictingParam]);
            }

            Log::debug('Fetching URL via ScrapingBee.', [
                'url' => $url,
                'params' => array_diff_key($query, ['url' => true]),
            ]);

            $response = $this->client->get(self::API_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'text/html,application/xhtml+xml',
                ],
                'query' => $query,
            ]);

            $statusCode = $response->getStatusCode();

            Log::debug('ScrapingBee response metadata.', [
                'url' => $url,
                'http_status' => $statusCode,
                'spb_auto_cost' => $response->getHeaderLine('Spb-auto-cost') ?: null,
                'spb_initial_status_code' => $response->getHeaderLine('Spb-initial-status-code') ?: null,
                'spb_request_id' => $response->getHeaderLine('Spb-Request-Id') ?: null,
            ]);


            if ($statusCode !== 200) {
                $requestId = $response->getHeaderLine('Spb-Request-Id');

                Log::warning('ScrapingBee returned a non-200 status.', [
                    'url' => $url,
                    'status' => $statusCode,
                    'request_id' => $requestId ?: null,
                ]);

                throw new RuntimeException(
                    "ScrapingBee returned HTTP {$statusCode}" .
                    ($requestId ? " (request id: {$requestId})" : '')
                );
            }

            $body = trim((string) $response->getBody());

            file_put_contents(
                storage_path('app/elpais-debug.html'),
                $body
            );

            if ($body === '') {
                throw new RuntimeException('ScrapingBee returned an empty body.');
            }

            Log::debug('Successfully fetched via ScrapingBee.', [
                'url' => $url,
                'content_length' => strlen($body),
                'request_id' => $response->getHeaderLine('Spb-Request-Id') ?: null,
            ]);

            return $body;
        } catch (RequestException $e) {
            Log::error('ScrapingBee request failed.', [
                'url' => $url,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'No response',
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('ScrapingBee fetch failed.', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->client->get(self::API_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'text/html,application/xhtml+xml',
                ],
                'query' => [
                    'url' => 'https://example.com',
                    'mode' => 'auto',
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }
}
