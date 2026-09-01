<?php

namespace App\Services\Crawler;

use App\Contracts\HtmlFetcher;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ElPaisUrlDiscoverer
{
    private string $baseUrl;

    public function __construct(private HtmlFetcher $httpClient)
    {
        $this->baseUrl = rtrim((string) config('services.elpais.base_url', 'https://www.elpais.com.uy'), '/');
    }

    /**
     * Discover premium article URLs from El Pais.
     */
    public function discover(): array
    {
        try {
            $premiumUrl = $this->baseUrl . '/noticias/premium';

            Log::info('Discovering El Pais premium articles.', [
                'url' => $premiumUrl,
            ]);

            $html = $this->httpClient->fetch($premiumUrl);
            $crawler = new Crawler($html);

            $urls = [];

            // Only inspect article-card links. Navigation and category links such as
            // /noticias/venezuela are not article pages.
            $crawler->filter('.PromoFlex h2.Promo-title a[href], .PromoFlex .Promo-media a[href]')->each(function (Crawler $node) use (&$urls) {
                $href = trim((string) $node->attr('href'));

                if ($href === '') {
                    return;
                }

                $absoluteUrl = $this->toAbsoluteUrl($href);

                if ($absoluteUrl === null || !$this->isArticleUrl($absoluteUrl)) {
                    return;
                }

                if (!in_array($absoluteUrl, $urls, true)) {
                    $urls[] = $absoluteUrl;
                }
            });

            Log::info('Discovered El Pais premium article URLs.', [
                'count' => count($urls),
            ]);

            return $urls;
        } catch (\Throwable $e) {
            Log::error('Failed to discover El Pais premium article URLs.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function isArticleUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);

        if (!$parsedUrl || empty($parsedUrl['host']) || $parsedUrl['host'] !== parse_url($this->baseUrl, PHP_URL_HOST)) {
            return false;
        }

        $path = trim($parsedUrl['path'] ?? '', '/');

        if ($path === '' || $path === 'noticias/premium') {
            return false;
        }

        return true;
    }

    private function toAbsoluteUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, 'javascript:') || str_starts_with($url, 'mailto:')) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $this->baseUrl . $url;
        }

        return $this->baseUrl . '/' . ltrim($url, '/');
    }
}
