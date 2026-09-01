<?php

namespace App\Services\Crawler;

use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class ElPaisArticleParser
{
    public function parse(string $html, string $url): array
    {
        $crawler = new Crawler($html);

        if (!$this->isArticle($crawler)) {
            throw new RuntimeException('Page does not appear to be an article.');
        }

        $title = $this->getTitle($crawler);
        if (!$title) {
            throw new RuntimeException('Article title could not be extracted.');
        }

        $publishedAt = $this->getPublishedDate($crawler);
        $author = $this->getAuthor($crawler);
        $content = $this->getContent($crawler);

        if (!$content) {
            throw new RuntimeException('Article content could not be extracted.');
        }

        $category = $this->getCategory($crawler);

        return [
            'title' => $title,
            'url' => $url,
            'published_at' => $publishedAt,
            'author' => $author,
            'content' => $content,
            'category' => $category,
            'source' => 'El Pais Uruguay',
            'language' => 'es',
        ];
    }

    private function isArticle(Crawler $crawler): bool
    {
        return $crawler->filter('article, [data-type="article"], .article-content, .story-body, .story-content')->count() > 0
            || ($crawler->filter('h1')->count() > 0 && $crawler->filter('p')->count() > 1);
    }

    private function getTitle(Crawler $crawler): ?string
    {
        $title = $this->getMetaContent($crawler, 'property', 'og:title');
        if ($title) {
            return $title;
        }

        $title = $this->getMetaContent($crawler, 'name', 'twitter:title');
        if ($title) {
            return $title;
        }

        if ($crawler->filter('h1')->count()) {
            return trim($crawler->filter('h1')->first()->text());
        }

        return null;
    }

    private function getPublishedDate(Crawler $crawler): ?string
    {
        $date = $this->getMetaContent($crawler, 'property', 'article:published_time');
        if ($date) {
            return $date;
        }

        $date = $this->getMetaContent($crawler, 'name', 'publish_date');
        if ($date) {
            return $date;
        }

        $date = $this->extractJsonLdField($crawler, 'datePublished');
        if ($date) {
            return $date;
        }

        $dateElements = $crawler->filter('time, [datetime], .publish-date, .article-date');
        if ($dateElements->count()) {
            $dateStr = $dateElements->first()->attr('datetime') ?? $dateElements->first()->text();

            return $dateStr ? trim($dateStr) : null;
        }

        return null;
    }

    private function getAuthor(Crawler $crawler): ?string
    {
        $author = $this->getMetaContent($crawler, 'name', 'author');
        if ($author) {
            return $author;
        }

        $author = $this->extractJsonLdField($crawler, 'author');
        if ($author) {
            return is_array($author) ? ($author['name'] ?? null) : $author;
        }

        $authorElements = $crawler->filter('.author, .by-line, [data-author], .writer-name');
        if ($authorElements->count()) {
            return trim($authorElements->first()->text());
        }

        return null;
    }

    private function getContent(Crawler $crawler): ?string
    {
        $selectors = [
            'article',
            '.article-content',
            '.article-body',
            '.story-body',
            '.story-content',
            '[data-type="article-body"]',
            '.content-body',
            'main article',
            'main',
        ];

        foreach ($selectors as $selector) {
            $content = $this->extractFromSelector($crawler, $selector);
            if ($content) {
                return $content;
            }
        }

        $paragraphs = [];
        $crawler->filter('p')->each(function ($node) use (&$paragraphs) {
            $text = trim($node->text());
            if ($text !== '' && strlen($text) > 20) {
                $paragraphs[] = $text;
            }
        });

        return !empty($paragraphs) ? implode("\n\n", $paragraphs) : null;
    }

    private function getCategory(Crawler $crawler): ?string
    {
        $category = $this->getMetaContent($crawler, 'name', 'article:section');
        if ($category) {
            return $category;
        }

        $categoryElements = $crawler->filter('.category, .section, [data-category], .breadcrumb a');
        if ($categoryElements->count()) {
            return trim($categoryElements->first()->text());
        }

        return 'General';
    }

    private function extractFromSelector(Crawler $crawler, string $selector): ?string
    {
        $elements = $crawler->filter($selector);
        if (!$elements->count()) {
            return null;
        }

        $paragraphs = [];
        $elements->first()->filter('p')->each(function ($node) use (&$paragraphs) {
            $text = trim($node->text());
            if ($text !== '' && strlen($text) > 10) {
                $paragraphs[] = $text;
            }
        });

        if (!empty($paragraphs)) {
            return implode("\n\n", $paragraphs);
        }

        $text = trim($elements->first()->text(''));

        return $text !== '' ? $text : null;
    }

    private function getMetaContent(Crawler $crawler, string $attribute, string $value): ?string
    {
        $selector = sprintf('meta[%s="%s"]', $attribute, $value);
        $elements = $crawler->filter($selector);

        if ($elements->count()) {
            $content = $elements->first()->attr('content');
            return $content ? trim($content) : null;
        }

        return null;
    }

    private function extractJsonLdField(Crawler $crawler, string $field): mixed
    {
        foreach ($crawler->filter('script[type="application/ld+json"]') as $script) {
            $json = json_decode(trim($script->textContent), true);

            if (!is_array($json)) {
                continue;
            }

            foreach ($this->normalizeJsonLd($json) as $item) {
                if (!isset($item[$field])) {
                    continue;
                }

                $value = $item[$field];

                if ($field === 'author' && is_array($value)) {
                    return $value['name'] ?? null;
                }

                return is_string($value) ? trim($value) : $value;
            }
        }

        return null;
    }

    private function normalizeJsonLd(array $json): array
    {
        if (isset($json['@graph']) && is_array($json['@graph'])) {
            return $json['@graph'];
        }

        if (isset($json['@type'])) {
            return [$json];
        }

        return array_values(array_filter($json, fn ($item) => is_array($item)));
    }
}
