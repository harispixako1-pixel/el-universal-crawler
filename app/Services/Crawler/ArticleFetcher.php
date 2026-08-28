<?php

namespace App\Services\Crawler;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ArticleFetcher
{
    /**
     * Fetch an article page.
     */
    public function fetch(string $url): string
    {
        try {
            $response = Http::timeout(15)
                ->retry(2, 1000)
                ->withHeaders([
                    'User-Agent' => 'ElUniversalCrawler/1.0',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            if ($response->failed()) {

                Log::error('Failed to fetch article page.', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                throw new RuntimeException(
                    "HTTP request failed with status {$response->status()}."
                );
            }

            return $response->body();

        } catch (\Throwable $e) {

            Log::error('Exception while fetching article page.', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}