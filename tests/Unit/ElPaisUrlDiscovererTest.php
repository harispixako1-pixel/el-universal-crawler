<?php

namespace Tests\Unit;

use App\Contracts\HtmlFetcher;
use App\Services\Crawler\ElPaisUrlDiscoverer;
use Tests\TestCase;

class ElPaisUrlDiscovererTest extends TestCase
{
    public function test_it_only_discovers_article_card_links(): void
    {
        $html = <<<'HTML'
            <html><body>
                <nav><a href="https://www.elpais.com.uy/noticias/venezuela">Venezuela</a></nav>
                <div class="PromoFlex">
                    <div class="Promo-media">
                        <a href="https://www.elpais.com.uy/mundo/example-article">Image</a>
                    </div>
                    <h2 class="Promo-title">
                        <a href="https://www.elpais.com.uy/mundo/example-article">Example article</a>
                    </h2>
                </div>
            </body></html>
        HTML;

        $fetcher = new class($html) implements HtmlFetcher {
            public function __construct(private string $html) {}

            public function fetch(string $url, array $params = []): string
            {
                return $this->html;
            }
        };

        $urls = (new ElPaisUrlDiscoverer($fetcher))->discover();

        $this->assertSame(['https://www.elpais.com.uy/mundo/example-article'], $urls);
    }
}
