<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\ViewingNotAllowedException;
use PHPUnit\Framework\TestCase;

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
