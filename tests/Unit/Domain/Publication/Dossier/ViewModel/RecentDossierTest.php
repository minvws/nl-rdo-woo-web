<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Mockery;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\ViewModel\RecentDossier;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;

final class RecentDossierTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getDossierNr')->andReturn($dossierNr = 'foo-123');
        $dossier->expects('getDocumentPrefix')->andReturn($prefix = 'BAR');
        $dossier->expects('getTitle')->andReturn($title = DossierTitle::create('foo bar baz'));
        $dossier->expects('getType')->andReturn($type = DossierType::COVENANT);
        $dossier->expects('getPublicationDate')->times(2)->andReturn($publicationDate = PlainDate::today());

        $viewmodel = RecentDossier::create($dossier);

        self::assertEquals($dossierNr, $viewmodel->reference->getDossierNr());
        self::assertEquals($title, $viewmodel->reference->getTitle());
        self::assertEquals($prefix, $viewmodel->reference->getDocumentPrefix());
        self::assertEquals($type, $viewmodel->reference->getType());
        self::assertEquals($publicationDate, $viewmodel->publicationDate);
    }
}
