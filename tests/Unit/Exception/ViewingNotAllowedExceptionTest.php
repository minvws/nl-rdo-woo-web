<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Shared\Exception\ViewingNotAllowedException;

class ViewingNotAllowedExceptionTest extends TestCase
{
    public function testForDossier(): void
    {
        self::assertStringContainsString(
            'Dossier not found',
            ViewingNotAllowedException::forDossier()->getMessage(),
        );
    }

    public function testForDossierOrDocument(): void
    {
        self::assertStringContainsString(
            'Dossier or document not found',
            ViewingNotAllowedException::forDossierOrDocument()->getMessage(),
        );
    }
}
