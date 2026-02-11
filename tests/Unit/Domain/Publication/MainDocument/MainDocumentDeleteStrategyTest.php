<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentDeleteStrategy;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;

final class MainDocumentDeleteStrategyTest extends UnitTestCase
{
    private EntityStorageService&MockInterface $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private SearchDispatcher&MockInterface $searchDispatcher;
    private MainDocumentDeleteStrategy $strategy;

    protected function setUp(): void
    {
        $this->searchDispatcher = Mockery::mock(SearchDispatcher::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->thumbnailStorageService = Mockery::mock(ThumbnailStorageService::class);
        $this->strategy = new MainDocumentDeleteStrategy(
            $this->searchDispatcher,
            $this->thumbnailStorageService,
            $this->entityStorageService,
        );

        parent::setUp();
    }

    public function testDeleteReturnsEarlyWhenDossierHasNoMainDocument(): void
    {
        $this->entityStorageService->shouldNotHaveBeenCalled();
        $dossier = Mockery::mock(AbstractDossier::class);

        $this->strategy->delete($dossier);
    }

    public function testDeleteMainDocument(): void
    {
        $document = Mockery::mock(CovenantMainDocument::class);
        $document->shouldReceive('getId->toRfc4122')->andReturn($documentId = 'foo-123');

        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getMainDocument')->andReturn($document);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($document);
        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($document);

        $this->searchDispatcher->expects('dispatchDeleteElasticDocumentCommand')->with($documentId);

        $this->strategy->delete($dossier);
    }

    public function testDeleteWithOverride(): void
    {
        /** @var MainDocumentDeleteStrategy&MockInterface $strategy */
        $strategy = Mockery::mock(MainDocumentDeleteStrategy::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $dossier = Mockery::mock(Covenant::class);

        $strategy->expects('delete')->with($dossier);

        $strategy->deleteWithOverride($dossier);
    }
}
