<?php

namespace App\Services\Crawler;

use App\Models\Article;
use App\Services\Crawler\ContentSanitizer;
use Illuminate\Support\Facades\Log;

class ElUniversalCrawler
{
    public function __construct(
        private UrlDiscoverer $urlDiscoverer,
        private ArticleFetcher $articleFetcher,
        private ArticleParser $articleParser,
        private ArticleNormalizer $articleNormalizer,
        private ContentSanitizer $contentSanitizer
    ) {
    }

    /**
     * Crawl new articles.
     *
     * The $limit represents the number of successfully
     * saved articles, not the number of URLs attempted.
     */
    public function crawl(int $limit = 10): array
    {
        $discoveredUrls = $this->urlDiscoverer->discover();

        $results = [
            'discovered' => count($discoveredUrls),
            'saved' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        /*
         * Process discovered URLs one by one.
         *
         * We continue processing until we successfully
         * save the requested number of articles.
         */
        foreach ($discoveredUrls as $url) {

            /*
             * Stop once the requested number of articles
             * has been successfully saved.
             */
            if ($results['saved'] >= $limit) {
                break;
            }

            /*
             * Incremental crawling:
             * Skip articles that already exist.
             */
            if (Article::where('url', $url)->exists()) {

                $results['skipped']++;

                Log::info(
                    'Article already exists, skipping.',
                    ['url' => $url]
                );

                continue;
            }

            try {

                /*
                 * Fetch article page.
                 */
                $html = $this->articleFetcher->fetch($url);

                /*
                 * Parse article.
                 */
                $article = $this->articleParser->parse(
                    $html,
                    $url
                );

                /*
                 * Normalize article data.
                 */
                $article = $this->articleNormalizer->normalize(
                    $article
                );

                // Extract language or fallback to 'es'
                $language = $article['language'] ?? 'es';

                // Sanitize content prior to saving
                $article['content'] = $this->contentSanitizer->sanitize(
                    $article['content'],
                    $language
                );

                /*
                 * Save article.
                 */
                Article::create($article);

                $results['saved']++;

                Log::info(
                    'Article saved successfully.',
                    ['url' => $url]
                );

            } catch (\Throwable $e) {

                /*
                 * A single failed article should NOT stop
                 * the crawler.
                 *
                 * Move to the next discovered URL.
                 */
                $results['failed']++;

                Log::error(
                    'Failed to process article. Continuing to next URL.',
                    [
                        'url' => $url,
                        'error' => $e->getMessage(),
                    ]
                );
            }

            /*
             * Basic throttling between requests.
             */
            sleep(1);
        }

        return $results;
    }
}