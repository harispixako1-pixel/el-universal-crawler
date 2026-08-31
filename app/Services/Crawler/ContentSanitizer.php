<?php

namespace App\Services\Crawler;

class ContentSanitizer
{
    private array $rules;
    private array $genericPatterns;

    public function __construct()
    {
        $this->rules = config('sanitizer.rules', []);
        $this->genericPatterns = config('sanitizer.generic_patterns', []);
    }

    /**
     * Sanitize article content string.
     */
    public function sanitize(?string $content, string $lang = 'es'): ?string
    {
        if (!$content) {
            return null;
        }

        $lang = strtolower($lang);
        if (!isset($this->rules[$lang])) {
            $lang = config('sanitizer.default_language', 'es');
        }

        // Split content into distinct paragraphs
        $paragraphs = preg_split('/\R{2,}/', $content);
        $cleanParagraphs = [];

        foreach ($paragraphs as $paragraph) {
            $trimmed = trim($paragraph);

            if ($this->shouldRemoveParagraph($trimmed, $lang)) {
                continue;
            }

            $cleanParagraphs[] = $trimmed;
        }

        return !empty($cleanParagraphs)
            ? implode("\n\n", $cleanParagraphs)
            : null;
    }

    /**
     * Evaluate whether a paragraph matches any junk/attribution patterns.
     */
    private function shouldRemoveParagraph(string $paragraph, string $lang): bool
    {
        if (mb_strlen($paragraph) === 0) {
            return true;
        }

        // 1. Generic rules
        foreach ($this->genericPatterns as $pattern) {
            if (preg_match($pattern, $paragraph)) {
                return true;
            }
        }

        // 2. Language-specific junk phrases
        $junkRules = $this->rules[$lang]['junk_phrases'] ?? [];
        foreach ($junkRules as $pattern) {
            if (preg_match($pattern, $paragraph)) {
                return true;
            }
        }

        // 3. Language-specific source & attribution rules
        $sourceRules = $this->rules[$lang]['source_attributions'] ?? [];
        foreach ($sourceRules as $pattern) {
            if (preg_match($pattern, $paragraph)) {
                return true;
            }
        }

        return false;
    }
}