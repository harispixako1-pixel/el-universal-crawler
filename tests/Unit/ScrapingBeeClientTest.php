<?php

namespace Tests\Unit;

use App\Services\Crawler\ScrapingBeeClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class ScrapingBeeClientTest extends TestCase
{
    public function test_fetch_builds_a_scrapingbee_request(): void
    {
        $history = [];
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], '<html><body>ok</body></html>'),
        ]));
        $handlerStack->push(Middleware::history($history));

        $client = new ScrapingBeeClient(
            'test-api-key',
            new Client(['handler' => $handlerStack])
        );

        $html = $client->fetch('https://example.com/article', [
            'return_page_source' => 'true',
        ]);

        $this->assertSame('<html><body>ok</body></html>', $html);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        parse_str($request->getUri()->getQuery(), $query);

        $this->assertSame('Bearer test-api-key', $request->getHeaderLine('Authorization'));
        $this->assertSame('https://example.com/article', $query['url']);
        $this->assertSame('auto', $query['mode']);
        $this->assertSame('true', $query['return_page_source']);
    }
}
