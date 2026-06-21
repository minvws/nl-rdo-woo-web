<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type;

use Mockery;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;

final class DossierReferenceTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getDossierNr')->andReturn($dossierNr = 'foo-123');
        $dossier->expects('getDocumentPrefix')->andReturn($documentPrefix = 'bar');
        $dossier->expects('getTitle')->andReturn($title = DossierTitle::create('foo bar'));
        $dossier->expects('getType')->andReturn($type = DossierType::ANNUAL_REPORT);

        $reference = DossierReference::fromEntity($dossier);

        self::assertEquals($dossierNr, $reference->getDossierNr());
        self::assertEquals($documentPrefix, $reference->getDocumentPrefix());
        self::assertEquals($title->toString(), $reference->getTitle());
        self::assertEquals($type, $reference->getType());
    }
}
