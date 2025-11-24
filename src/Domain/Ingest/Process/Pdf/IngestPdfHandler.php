<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\Pdf;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shared\Doctrine\LegacyNamespaceHelper;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Worker\Pdf\Extractor\PagecountExtractor;
use Shared\Service\Worker\PdfProcessor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

/**
 * Ingest a PDF file into the system. It will extract all pages from the pdf and emits a message for each page.
 */
#[AsMessageHandler]
final readonly class IngestPdfHandler
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private PagecountExtractor $extractor,
        private PdfProcessor $processor,
        private LoggerInterface $logger,
        private IngestDispatcher $ingestDispatcher,
    ) {
    }

    public function __invoke(IngestPdfCommand $message): void
    {
        $entityClass = LegacyNamespaceHelper::normalizeClassName($message->getEntityClass());
        $entity = $this->doctrine->getRepository($entityClass)->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No entity found in IngestPdfHandler', [
                'id' => $message->getEntityId()->toRfc4122(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        /** @var EntityWithFileInfo $entity */
        Assert::isInstanceOf($entity, EntityWithFileInfo::class);
        Assert::true($entity->getFileInfo()->isPaginatable(), 'Entity is not paginatable');

        $this->ensurePageCountIsSet($entity);

        // Process document and store in elastic as a whole, including extracted metadata
        $this->processor->processEntity($entity);

        // Go and ingest all pages in the document
        for ($pageNr = 1; $pageNr <= $entity->getFileInfo()->getPageCount(); $pageNr++) {
            $this->ingestDispatcher->dispatchIngestPdfPageCommand(
                $message->getEntityId(),
                $message->getEntityClass(),
                $pageNr,
            );
        }
    }

    private function ensurePageCountIsSet(EntityWithFileInfo $entity): void
    {
        if ($entity->getFileInfo()->getPageCount() !== null) {
            return;
        }

        $this->extractor->extract($entity);
        $result = $this->extractor->getOutput();
        $pageCount = $result?->isSuccessful() ? $result->numberOfPages : 0;

        $entity->getFileInfo()->setPageCount($pageCount);

        $this->doctrine->persist($entity);
        $this->doctrine->flush();
    }
}
