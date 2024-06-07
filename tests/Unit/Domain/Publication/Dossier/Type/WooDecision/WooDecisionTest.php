<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use PHPUnit\Framework\TestCase;

final class WooDecisionTest extends TestCase
{
    public function testGetDownloadFilePrefix(): void
    {
        $dossier = new WooDecision();
        $dossier->setDossierNr('tst-123');

        $translatableMessage = $dossier->getDownloadFilePrefix();

        self::assertEquals(
            'admin.dossiers.decision.number',
            $translatableMessage->getMessage(),
        );

        self::assertEquals(
            [
                'dossierNr' => 'tst-123',
            ],
            $translatableMessage->getPlaceholders(),
        );
    }
}
