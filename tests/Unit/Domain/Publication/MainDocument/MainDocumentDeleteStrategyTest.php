<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\MainDocument\MainDocumentDeleteStrategy;
use App\Service\Storage\DocumentStorageService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class MainDocumentDeleteStrategyTest extends MockeryTestCase
{
    private DocumentStorageService&MockInterface $documentStorage;
    private MainDocumentDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->strategy = new MainDocumentDeleteStrategy($this->documentStorage);

        parent::setUp();
    }

    public function testDeleteReturnsEarlyWhenDossierHasNoMainDocument(): void
    {
        $this->documentStorage->shouldNotHaveBeenCalled();
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->strategy->delete($dossier);
    }

    public function testDeleteMainDocument(): void
    {
        $document = \Mockery::mock(CovenantDocument::class);

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getDocument')->andReturn($document);

        $this->documentStorage->expects('removeFileForEntity')->with($document);

        $this->strategy->delete($dossier);
    }
}
