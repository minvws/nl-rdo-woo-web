<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentDeleteStrategy;
use App\Domain\Search\SearchDispatcher;
use App\Service\Storage\EntityStorageService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class MainDocumentDeleteStrategyTest extends MockeryTestCase
{
    private EntityStorageService&MockInterface $entityStorageService;
    private SearchDispatcher&MockInterface $searchDispatcher;
    private MainDocumentDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->searchDispatcher = \Mockery::mock(SearchDispatcher::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->strategy = new MainDocumentDeleteStrategy(
            $this->searchDispatcher,
            $this->entityStorageService,
        );

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
        $document->shouldReceive('getId->toRfc4122')->andReturn($documentId = 'foo-123');

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getMainDocument')->andReturn($document);

        $this->entityStorageService->expects('removeFileForEntity')->with($document);

        $this->searchDispatcher->expects('dispatchDeleteElasticDocumentCommand')->with($documentId);

        $this->strategy->delete($dossier);
    }
}
