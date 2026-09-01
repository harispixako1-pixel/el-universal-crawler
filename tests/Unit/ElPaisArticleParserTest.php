<?php

namespace Tests\Unit;

use App\Services\Crawler\ElPaisArticleParser;
use RuntimeException;
use Tests\TestCase;

class ElPaisArticleParserTest extends TestCase
{
    public function test_it_extracts_article_body_and_json_ld_metadata(): void
    {
        $html = <<<'HTML'
            <html>
                <head>
                    <meta property="og:title" content="Example article">
                    <script type="application/ld+json">
                        {"@type":"NewsArticle","headline":"Example article","datePublished":"2026-09-01T10:00:00Z","author":{"name":"Example Author"}}
                    </script>
                </head>
                <body>
                    <main class="Page-articleBody">
                        <p>The first paragraph of the article.</p>
                        <p>The second paragraph contains the actual content.</p>
                    </main>
                </body>
            </html>
        HTML;

        $article = (new ElPaisArticleParser())->parse($html, 'https://www.elpais.com.uy/mundo/example-article');

        $this->assertSame('Example article', $article['title']);
        $this->assertSame('2026-09-01T10:00:00Z', $article['published_at']);
        $this->assertSame('Example Author', $article['author']);
        $this->assertStringContainsString('actual content', $article['content']);
    }

    public function test_it_rejects_a_tag_page_with_generic_paragraphs(): void
    {
        $html = <<<'HTML'
            <html>
                <head><meta property="og:title" content="Venezuela"></head>
                <body>
                    <h1>Noticias de Venezuela</h1>
                    <p>Contenido de una página de categoría.</p>
                    <p>Otro elemento de la lista.</p>
                </body>
            </html>
        HTML;

        $this->expectException(RuntimeException::class);

        (new ElPaisArticleParser())->parse($html, 'https://www.elpais.com.uy/noticias/venezuela');
    }

    public function test_it_prefers_json_ld_body_over_paywall_markup(): void
    {
        $html = <<<'HTML'
            <html class="ArticlePage">
                <head>
                    <meta property="og:title" content="Premium article">
                    <script type="application/ld+json">
                        {"@type":"NewsArticle","headline":"Premium article","datePublished":"2026-09-01T02:50:00-03:00","author":[{"@type":"Person","name":"Isabelle Chaquiriand"}],"articleBody":"First real paragraph. Second real paragraph."}
                    </script>
                </head>
                <body>
                    <div class="Page-articleBody">
                        <div class="RichTextArticleBody" style="display: none;"><p>&nbsp;</p></div>
                        <div class="contenido-exclusivo-nota"><p>Contenido Exclusivo</p><p>Conocé nuestros planes</p></div>
                    </div>
                </body>
            </html>
        HTML;

        $article = (new ElPaisArticleParser())->parse($html, 'https://www.elpais.com.uy/opinion/example');

        $this->assertSame('Isabelle Chaquiriand', $article['author']);
        $this->assertSame('First real paragraph. Second real paragraph.', $article['content']);
        $this->assertStringNotContainsString('Contenido Exclusivo', $article['content']);
    }
}
