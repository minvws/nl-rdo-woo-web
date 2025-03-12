<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\SubType;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\IndexException;
use App\Domain\Search\Index\SubType\Mapper\ElasticSubTypeMapperInterface;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Domain\Search\Index\Updater\PageIndexUpdater;
use App\Service\Elastic\ElasticService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class SubTypeIndexerTest extends MockeryTestCase
{
    private ElasticSubTypeMapperInterface&MockInterface $firstMapper;
    private ElasticSubTypeMapperInterface&MockInterface $secondMapper;
    private ElasticService&MockInterface $elasticService;
    private PageIndexUpdater&MockInterface $pageIndexUpdater;
    private SubTypeIndexer $indexer;

    public function setUp(): void
    {
        $this->firstMapper = \Mockery::mock(ElasticSubTypeMapperInterface::class);
        $this->secondMapper = \Mockery::mock(ElasticSubTypeMapperInterface::class);

        $this->elasticService = \Mockery::mock(ElasticService::class);
        $this->pageIndexUpdater = \Mockery::mock(PageIndexUpdater::class);

        $this->indexer = new SubTypeIndexer(
            $this->elasticService,
            $this->pageIndexUpdater,
            new \ArrayIterator([$this->firstMapper, $this->secondMapper]),
        );
    }

    public function testIndex(): void
    {
        $documentId = Uuid::v6();
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($documentId);

        $metadata = ['foo' => 'bar'];
        $pages = ['dummy', 'content'];

        $mappedDoc = \Mockery::mock(ElasticDocument::class);

        $this->firstMapper->expects('supports')->with($document)->andReturnTrue();
        $this->firstMapper->expects('map')->with($document, $metadata, $pages)->andReturn($mappedDoc);

        $this->elasticService->expects('updateDocument')->with($mappedDoc);

        $this->indexer->index($document, $metadata, $pages);
    }

    public function testIndexThrowsExceptionWhenNoMapperSupportsTheEntity(): void
    {
        $document = \Mockery::mock(Document::class);

        $this->firstMapper->expects('supports')->with($document)->andReturnFalse();
        $this->secondMapper->expects('supports')->with($document)->andReturnFalse();

        $this->expectException(IndexException::class);

        $this->indexer->index($document);
    }

    public function testIndexUsesSecondMapperWhenFirstDoesNotSupportTheEntity(): void
    {
        $documentId = Uuid::v6();
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($documentId);

        $metadata = ['foo' => 'bar'];
        $pages = ['dummy', 'content'];

        $mappedDoc = \Mockery::mock(ElasticDocument::class);

        $this->firstMapper->expects('supports')->with($document)->andReturnFalse();
        $this->secondMapper->expects('supports')->with($document)->andReturnTrue();
        $this->secondMapper->expects('map')->with($document, $metadata, $pages)->andReturn($mappedDoc);

        $this->elasticService->expects('updateDocument')->with($mappedDoc);

        $this->indexer->index($document, $metadata, $pages);
    }

    public function testRemove(): void
    {
        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getId->toRfc4122')->andReturn($documentId = 'foo-123');

        $this->elasticService->expects('removeDocument')->with($documentId);

        $this->indexer->remove($entity);
    }

    public function testUpdatePage(): void
    {
        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getId->toRfc4122')->andReturn($documentId = 'foo-123');
        $pageNr = 12;
        $content = 'foo bar';

        $this->pageIndexUpdater->expects('update')->with($documentId, $pageNr, $content);

        $this->indexer->updatePage($entity, $pageNr, $content);
    }
}
