<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentDeleteStrategy;
use App\Service\Storage\EntityStorageService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class MainDocumentDeleteStrategyTest extends MockeryTestCase
{
    private EntityStorageService&MockInterface $entityStorageService;
    private MainDocumentDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->strategy = new MainDocumentDeleteStrategy($this->entityStorageService);

        parent::setUp();
    }

    public function testDeleteReturnsEarlyWhenDossierHasNoMainDocument(): void
    {
        $this->entityStorageService->shouldNotHaveBeenCalled();
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->strategy->delete($dossier);
    }

    public function testDeleteMainDocument(): void
    {
        $document = \Mockery::mock(CovenantMainDocument::class);

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getMainDocument')->andReturn($document);

        $this->entityStorageService->expects('removeFileForEntity')->with($document);

        $this->strategy->delete($dossier);
    }
}
