<?php

namespace App\Services\Crawler;

use App\Contracts\HtmlFetcher;
use App\Models\Article;
use Illuminate\Support\Facades\Log;

class ElPaisCrawler
{
    public function __construct(
        private HtmlFetcher $httpClient,
        private ElPaisUrlDiscoverer $urlDiscoverer,
        private ElPaisArticleParser $articleParser,
        private ArticleNormalizer $articleNormalizer,
        private ContentSanitizer $contentSanitizer
    ) {
    }

    /**
     * Crawl El Pais premium articles.
     */
    public function crawl(int $limit = 10): array
    {
        $results = [
            'discovered' => 0,
            'saved' => 0,
            'skipped' => 0,
            'failed' => 0,
            'error' => null,
        ];

        $discoveredUrls = $this->urlDiscoverer->discover();
        $results['discovered'] = count($discoveredUrls);

        if (empty($discoveredUrls)) {
            $results['error'] = 'No article URLs were discovered.';
            Log::warning('El Pais crawler: no URLs discovered.');

            return $results;
        }

        foreach ($discoveredUrls as $url) {
            if ($results['saved'] >= $limit) {
                break;
            }

            if (Article::where('url', $url)->exists()) {
                $results['skipped']++;
                Log::info('El Pais article already exists.', ['url' => $url]);
                continue;
            }

            try {
                $html = $this->httpClient->fetch($url);
                $article = $this->articleParser->parse($html, $url);
                $article = $this->articleNormalizer->normalize($article);

                $article['content'] = $this->contentSanitizer->sanitize(
                    $article['content'],
                    $article['language'] ?? 'es'
                );

                Article::create($article);
                $results['saved']++;

                Log::info('El Pais article saved.', ['url' => $url]);
            } catch (\Throwable $e) {
                $results['failed']++;
                Log::error('El Pais: failed to process article.', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            sleep(2);
        }

        return $results;
    }
}
