<?php

namespace App\Services\Crawler;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class UrlDiscoverer
{
    private string $baseUrl = 'https://www.eluniversal.com.mx';

    /**
     * Sections that can contain article pages.
     */
    private array $sections = [
        'nacion',
        'mundo',
        'metropoli',
        'estados',
        'cartera',
        'deportes',
        'cultura',
        'espectaculos',
        'opinion',
    ];

    /**
     * Discover article candidate URLs from homepage.
     */
    public function discover(): array
    {
        try {
            $response = Http::timeout(15)
                ->get($this->baseUrl);

            if ($response->failed()) {
                Log::error('Failed to fetch El Universal homepage.', [
                    'url' => $this->baseUrl,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $crawler = new Crawler($response->body());

            $links = $crawler
                ->filter('a[href]')
                ->each(function ($node) {
                    return $node->attr('href');
                });

            $articleUrls = [];

            foreach ($links as $url) {
                $url = $this->normalizeUrl($url);

                if (!$this->isArticleCandidate($url)) {
                    continue;
                }

                $articleUrls[] = $url;
            }

            return array_values(array_unique($articleUrls));

        } catch (\Throwable $e) {

            Log::error('Failed to discover article URLs.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Determine whether a URL is a likely article URL.
     */
    private function isArticleCandidate(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        /*
         * Only allow El Universal URLs.
         */
        if (!str_starts_with($url, $this->baseUrl . '/')) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (!$path) {
            return false;
        }

        $segments = array_values(
            array_filter(
                explode('/', trim($path, '/'))
            )
        );

        if (empty($segments)) {
            return false;
        }

        $section = $segments[0];

        /*
         * Ignore pages that are not known article sections.
         */
        if (!in_array($section, $this->sections, true)) {
            return false;
        }

        /*
         * Normal article structure:
         *
         * /nacion/article-slug/
         * /mundo/article-slug/
         * /cultura/article-slug/
         */
        if ($section !== 'opinion') {
            return count($segments) === 2;
        }

        /*
         * Opinion articles normally have:
         *
         * /opinion/author/article-slug/
         *
         * Therefore:
         *
         * /opinion/bajo-reserva/
         *
         * is NOT considered an article.
         */
        return count($segments) >= 3;
    }

    /**
     * Convert relative URL to absolute URL.
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $this->baseUrl . $url;
        }

        /*
         * Remove URL fragments.
         */
        $url = preg_replace('/#.*$/', '', $url);

        return $url;
    }
}