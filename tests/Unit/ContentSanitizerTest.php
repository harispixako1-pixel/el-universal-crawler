<?php

namespace Tests\Unit;

use App\Services\Crawler\ContentSanitizer;
use Tests\TestCase;

class ContentSanitizerTest extends TestCase
{
    private ContentSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new ContentSanitizer();
    }

    public function test_spanish_content_sanitization(): void
    {
        $dirtyContent = implode("\n\n", [
            "El presidente anunció una nueva reforma económica este lunes.",
            "[PUBLICIDAD]",
            "Lee también: Las mejores estrategias de inversión para 2026",
            "La medida busca controlar la inflación acumulada del año.",
            "Fuente: Agencia EFE",
            "Síguenos en nuestras redes sociales para más información.",
        ]);

        $sanitized = $this->sanitizer->sanitize($dirtyContent, 'es');

        $expected = implode("\n\n", [
            "El presidente anunció una nueva reforma económica este lunes.",
            "La medida busca controlar la inflación acumulada del año.",
        ]);

        $this->assertEquals($expected, $sanitized);
    }

    public function test_english_content_sanitization(): void
    {
        $dirtyContent = implode("\n\n", [
            "The central bank decided to lower interest rates today.",
            "[Advertisement]",
            "Read more at: Markets update and stock analysis",
            "Officials mentioned that consumer confidence remains steady.",
            "Source: Financial Times",
            "Subscribe to our newsletter for daily updates.",
        ]);

        $sanitized = $this->sanitizer->sanitize($dirtyContent, 'en');

        $expected = implode("\n\n", [
            "The central bank decided to lower interest rates today.",
            "Officials mentioned that consumer confidence remains steady.",
        ]);

        $this->assertEquals($expected, $sanitized);
    }

    public function test_portuguese_content_sanitization(): void
    {
        $dirtyContent = implode("\n\n", [
            "O governo aprovou hoje o novo plano de desenvolvimento tecnológico.",
            "[Publicidade]",
            "Leia também: Como a inovação está mudando o mercado",
            "A expectativa é gerar milhares de novos empregos até o fim do ano.",
            "Fonte: Agência Lusa",
            "Siga-nos nas redes sociais para acompanhar as novidades.",
        ]);

        $sanitized = $this->sanitizer->sanitize($dirtyContent, 'pt');

        $expected = implode("\n\n", [
            "O governo aprovou hoje o novo plano de desenvolvimento tecnológico.",
            "A expectativa é gerar milhares de novos empregos até o fim do ano.",
        ]);

        $this->assertEquals($expected, $sanitized);
    }

    public function test_generic_url_removal(): void
    {
        $dirtyContent = implode("\n\n", [
            "Este é um artigo importante sobre sustentabilidade.",
            "https://www.example.com/junk-link",
            "A reciclagem reduziu os resíduos industriais significativamente.",
        ]);

        $sanitized = $this->sanitizer->sanitize($dirtyContent, 'es');

        $expected = implode("\n\n", [
            "Este é um artigo importante sobre sustentabilidade.",
            "A reciclagem reduziu os resíduos industriais significativamente.",
        ]);

        $this->assertEquals($expected, $sanitized);
    }
}