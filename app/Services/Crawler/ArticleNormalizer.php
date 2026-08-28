<?php

namespace App\Services\Crawler;

use Carbon\Carbon;

class ArticleNormalizer
{
    /**
     * Normalize parsed article data.
     */
    public function normalize(array $article): array
    {
        return [
            'title' => $this->cleanText(
                $article['title']
            ),

            'url' => trim(
                $article['url']
            ),

            'published_at' => $this->normalizeDate(
                $article['published_at']
            ),

            'author' => $this->cleanText(
                $article['author']
            ),

            'content' => $this->cleanContent(
                $article['content']
            ),

            'category' => $this->cleanText(
                $article['category']
            ),

            'source' => $this->cleanText(
                $article['source']
            ) ?? 'El Universal',
        ];
    }

    private function cleanText(
        ?string $value
    ): ?string {
        if (!$value) {
            return null;
        }

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim($value)
        );

        return $value ?: null;
    }

    private function cleanContent(
        ?string $content
    ): ?string {
        if (!$content) {
            return null;
        }

        $paragraphs = preg_split(
            '/\R{2,}/',
            trim($content)
        );

        $paragraphs = array_map(
            fn($paragraph) => trim(
                preg_replace('/\s+/', ' ', $paragraph)
            ),
            $paragraphs
        );

        $paragraphs = array_filter(
            $paragraphs,
            fn($paragraph) => $paragraph !== ''
        );

        return $paragraphs
            ? implode("\n\n", $paragraphs)
            : null;
    }

    private function normalizeDate(
        ?string $date
    ): ?string {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)
                ->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}