<?php

namespace App\Services\Crawler;

use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class ArticleParser
{
    public function parse(string $html, string $url): array
    {
        $crawler = new Crawler($html);

        /*
         * Make sure this is actually an article.
         */
        if (!$this->isActualArticle($crawler)) {
            throw new RuntimeException(
                'Page does not appear to be an actual article.'
            );
        }

        /*
         * Title
         */
        $title = $this->getMetaContent(
            $crawler,
            'property',
            'og:title'
        );

        if (!$title && $crawler->filter('h1')->count()) {
            $title = trim(
                $crawler->filter('h1')->first()->text()
            );
        }

        if (!$title) {
            throw new RuntimeException(
                'Article title could not be extracted.'
            );
        }

        /*
         * Publication date
         */
        $publishedAt = $this->getMetaContent(
            $crawler,
            'name',
            'fecha_publicacion'
        );

        if (!$publishedAt) {
            $publishedAt = $this->extractJsonLdField(
                $crawler,
                'datePublished'
            );
        }

        /*
         * Author
         *
         * Author may legitimately be missing.
         */
        $author = $this->getMetaContent(
            $crawler,
            'name',
            'autor'
        );

        if (!$author) {
            $author = $this->extractJsonLdField(
                $crawler,
                'author'
            );
        }

        /*
         * Category
         */
        $category = $this->getMetaContent(
            $crawler,
            'name',
            'category'
        );

        /*
         * Source
         */
        $source = $this->getMetaContent(
            $crawler,
            'property',
            'og:site_name'
        ) ?? 'El Universal';

        /*
         * Content
         */
        $content = $this->extractContent($crawler);

        if (!$content) {
            throw new RuntimeException(
                'Article content could not be extracted.'
            );
        }

        return [
            'title' => trim($title),
            'url' => $url,
            'published_at' => $this->normalizeDate($publishedAt),
            'author' => $author ? trim($author) : null,
            'content' => $content,
            'category' => $category ? trim($category) : null,
            'source' => trim($source),
        ];
    }

    /**
     * Verify that the page is an actual article.
     */
    private function isActualArticle(Crawler $crawler): bool
    {
        /*
         * First look for NewsArticle JSON-LD.
         */
        if ($this->containsNewsArticleJsonLd($crawler)) {
            return true;
        }

        /*
         * Fallback:
         * article pages should have H1 + meaningful content.
         */
        if ($crawler->filter('h1')->count() === 0) {
            return false;
        }

        $content = $this->extractContent($crawler);

        if (!$content) {
            return false;
        }

        return mb_strlen($content) >= 100;
    }

    /**
     * Check whether JSON-LD contains NewsArticle.
     */
    private function containsNewsArticleJsonLd(Crawler $crawler): bool
    {
        $scripts = $crawler->filter(
            'script[type="application/ld+json"]'
        );

        foreach ($scripts as $script) {
            $json = trim($script->textContent);

            if ($json === '') {
                continue;
            }

            $data = json_decode($json, true);

            if (!is_array($data)) {
                continue;
            }

            /*
             * Single JSON-LD object.
             */
            if (
                isset($data['@type']) &&
                (
                    $data['@type'] === 'NewsArticle' ||
                    $data['@type'] === 'Article'
                )
            ) {
                return true;
            }

            /*
             * Multiple JSON-LD objects.
             */
            foreach ($data as $item) {
                if (!is_array($item)) {
                    continue;
                }

                if (
                    isset($item['@type']) &&
                    (
                        $item['@type'] === 'NewsArticle' ||
                        $item['@type'] === 'Article'
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract article content raw paragraphs.
     */
    private function extractContent(Crawler $crawler): ?string
    {
        $selectors = [
            'div.body',
            '.body',
            'article',
        ];

        foreach ($selectors as $selector) {

            if ($crawler->filter($selector)->count() === 0) {
                continue;
            }

            $paragraphs = $crawler
                ->filter($selector)
                ->filter('p')
                ->each(function ($node) {
                    return trim($node->text());
                });

            $validParagraphs = array_filter(
                $paragraphs,
                fn($paragraph) => $paragraph !== ''
            );

            if (!empty($validParagraphs)) {
                return implode(
                    "\n\n",
                    $validParagraphs
                );
            }
        }

        return null;
    }

    /**
     * Get meta tag content.
     */
    private function getMetaContent(
        Crawler $crawler,
        string $attribute,
        string $value
    ): ?string {
        $selector = sprintf(
            'meta[%s="%s"]',
            $attribute,
            $value
        );

        if ($crawler->filter($selector)->count() === 0) {
            return null;
        }

        $content = $crawler
            ->filter($selector)
            ->first()
            ->attr('content');

        return $content
            ? trim($content)
            : null;
    }

    /**
     * Extract a field from NewsArticle JSON-LD.
     */
    private function extractJsonLdField(
        Crawler $crawler,
        string $field
    ): ?string {
        $scripts = $crawler->filter(
            'script[type="application/ld+json"]'
        );

        foreach ($scripts as $script) {

            $json = trim($script->textContent);

            if ($json === '') {
                continue;
            }

            $data = json_decode($json, true);

            if (!is_array($data)) {
                continue;
            }

            /*
             * Normalize single object to array.
             */
            $items = isset($data['@type'])
                ? [$data]
                : $data;

            foreach ($items as $item) {

                if (!is_array($item)) {
                    continue;
                }

                if (
                    !isset($item['@type']) ||
                    !in_array(
                        $item['@type'],
                        ['NewsArticle', 'Article'],
                        true
                    )
                ) {
                    continue;
                }

                if (!isset($item[$field])) {
                    continue;
                }

                $value = $item[$field];

                /*
                 * JSON-LD author can be an object.
                 */
                if (
                    $field === 'author' &&
                    is_array($value)
                ) {
                    return $value['name'] ?? null;
                }

                if (is_string($value)) {
                    return trim($value);
                }
            }
        }

        return null;
    }

    /**
     * Normalize publication date.
     */
    private function normalizeDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return null;
        }

        return date(
            'Y-m-d H:i:s',
            $timestamp
        );
    }
}