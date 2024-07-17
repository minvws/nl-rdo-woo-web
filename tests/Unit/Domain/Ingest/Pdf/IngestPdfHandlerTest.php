<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Pdf;

use App\Domain\Ingest\Pdf\IngestPdfCommand;
use App\Domain\Ingest\Pdf\IngestPdfHandler;
use App\Domain\Ingest\PdfPage\IngestPdfPageCommand;
use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use App\Entity\FileInfo;
use App\Service\Worker\Pdf\Extractor\PagecountExtractor;
use App\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use App\Service\Worker\PdfProcessor;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\Matcher\Closure as ClosureMatcher;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class IngestPdfHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private PagecountExtractor&MockInterface $extractor;
    private MessageBusInterface&MockInterface $bus;
    private PdfProcessor&MockInterface $processor;
    private LoggerInterface&MockInterface $logger;
    private EntityRepository&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->extractor = \Mockery::mock(PagecountExtractor::class);
        $this->bus = \Mockery::mock(MessageBusInterface::class);
        $this->processor = \Mockery::mock(PdfProcessor::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->repository = \Mockery::mock(EntityRepository::class);
    }

    public function testInvokeWithoutForce(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $message = new IngestPdfCommand(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = $entity::class,
            $forceRefresh = false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->doctrine->shouldReceive('persist')->with($entity);
        $this->doctrine->shouldReceive('flush');

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->extractor->shouldReceive('extract')->once()->with($entity, $forceRefresh);

        $pdftkPageCountResult = \Mockery::mock(PdftkPageCountResult::class);
        $pdftkPageCountResult->shouldReceive('isSuccessful')->once()->andReturnTrue();
        $pdftkPageCountResult->numberOfPages = $pageCount = 2;

        $this->extractor->shouldReceive('getOutPut')->once()->with()->andReturn($pdftkPageCountResult);

        $entity->shouldReceive('setPageCount')->once()->with($pageCount);
        $fileInfo->shouldReceive('setPageCount')->once()->with($pageCount);

        $this->processor->shouldReceive('processEntity')->once()->with($entity, $forceRefresh);

        $this->bus->shouldReceive('dispatch')
            ->once()
            ->with($this->ingestPdfPageMessageMatcher($id, $entityClass, $forceRefresh, pageNr: 1))
            ->andReturn(new Envelope(new \stdClass()));
        $this->bus->shouldReceive('dispatch')
            ->once()
            ->with($this->ingestPdfPageMessageMatcher($id, $entityClass, $forceRefresh, pageNr: 2))
            ->andReturn(new Envelope(new \stdClass()));

        $handler = new IngestPdfHandler($this->doctrine, $this->extractor, $this->bus, $this->processor, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWithForce(): void
    {
        $message = new IngestPdfCommand(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            $forceRefresh = true,
        );

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->doctrine->shouldReceive('persist')->with($entity);
        $this->doctrine->shouldReceive('flush');

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->extractor->shouldReceive('extract')->once()->with($entity, $forceRefresh);

        $pdftkPageCountResult = \Mockery::mock(PdftkPageCountResult::class);
        $pdftkPageCountResult->shouldReceive('isSuccessful')->once()->andReturnTrue();
        $pdftkPageCountResult->numberOfPages = $pageCount = 2;

        $this->extractor->shouldReceive('getOutPut')->once()->with()->andReturn($pdftkPageCountResult);

        $entity->shouldReceive('setPageCount')->once()->with($pageCount);
        $fileInfo->shouldReceive('setPageCount')->once()->with($pageCount);

        $this->processor->shouldReceive('processEntity')->once()->with($entity, $forceRefresh);

        $this->bus->shouldReceive('dispatch')
            ->once()
            ->with($this->ingestPdfPageMessageMatcher($id, $entityClass, $forceRefresh, pageNr: 1))
            ->andReturn(new Envelope(new \stdClass()));
        $this->bus->shouldReceive('dispatch')
            ->once()
            ->with($this->ingestPdfPageMessageMatcher($id, $entityClass, $forceRefresh, pageNr: 2))
            ->andReturn(new Envelope(new \stdClass()));

        $handler = new IngestPdfHandler($this->doctrine, $this->extractor, $this->bus, $this->processor, $this->logger);
        $handler->__invoke($message);
    }

    public function testInvokeWhenEntityCannotBeFound(): void
    {
        $message = new IngestPdfCommand(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturnNull();

        $this->logger->shouldReceive('warning')->once()->with('No entity found in IngestPdfHandler', [
            'id' => $id,
            'class' => $entityClass,
        ]);

        $this->doctrine->shouldNotReceive('persist');
        $this->doctrine->shouldNotReceive('flush');
        $this->extractor->shouldNotReceive('extract');
        $this->extractor->shouldNotReceive('getOutPut');
        $entity->shouldNotReceive('setPageCount');
        $this->processor->shouldNotReceive('processEntity');
        $this->bus->shouldNotReceive('dispatch');

        $handler = new IngestPdfHandler($this->doctrine, $this->extractor, $this->bus, $this->processor, $this->logger);
        $handler->__invoke($message);
    }

    private function ingestPdfPageMessageMatcher(Uuid $id, string $entityClass, bool $forceRefresh, int $pageNr): ClosureMatcher
    {
        return \Mockery::on(function (IngestPdfPageCommand $message) use ($id, $entityClass, $forceRefresh, $pageNr) {
            return $message->getEntityId() === $id
                && $message->getEntityClass() === $entityClass
                && $message->getForceRefresh() === $forceRefresh
                && $message->getPageNr() === $pageNr;
        });
    }
}
