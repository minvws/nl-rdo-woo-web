<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Tests\Unit\UnitTestCase;

final class DossierReferenceTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr = 'foo-123');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($documentPrefix = 'bar');
        $dossier->shouldReceive('getTitle')->andReturn($title = 'foo bar');
        $dossier->shouldReceive('getType')->andReturn($type = DossierType::ANNUAL_REPORT);

        $reference = DossierReference::fromEntity($dossier);

        self::assertEquals($dossierNr, $reference->getDossierNr());
        self::assertEquals($documentPrefix, $reference->getDocumentPrefix());
        self::assertEquals($title, $reference->getTitle());
        self::assertEquals($type, $reference->getType());
    }
}
