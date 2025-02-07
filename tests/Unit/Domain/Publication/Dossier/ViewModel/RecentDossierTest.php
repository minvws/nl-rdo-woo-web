<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\ViewModel\RecentDossier;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class RecentDossierTest extends MockeryTestCase
{
    public function testCreate(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr = 'foo-123');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($prefix = 'BAR');
        $dossier->shouldReceive('getTitle')->andReturn($title = 'foo bar baz');
        $dossier->shouldReceive('getType')->andReturn($type = DossierType::COVENANT);
        $dossier->shouldReceive('getPublicationDate')->andReturn($publicationDate = new \DateTimeImmutable());

        $viewmodel = RecentDossier::create($dossier);

        self::assertEquals($dossierNr, $viewmodel->reference->getDossierNr());
        self::assertEquals($title, $viewmodel->reference->getTitle());
        self::assertEquals($prefix, $viewmodel->reference->getDocumentPrefix());
        self::assertEquals($type, $viewmodel->reference->getType());
        self::assertEquals($publicationDate, $viewmodel->publicationDate);
    }
}
