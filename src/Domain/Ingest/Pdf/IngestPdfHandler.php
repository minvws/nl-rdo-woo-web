<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Pdf;

use App\Domain\Ingest\PdfPage\IngestPdfPageCommand;
use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use App\Service\Worker\Pdf\Extractor\PagecountExtractor;
use App\Service\Worker\PdfProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

/**
 * Ingest a PDF file into the system. It will extract all pages fromm the pdf and emits a message for each page.
 */
#[AsMessageHandler]
final readonly class IngestPdfHandler
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private PagecountExtractor $extractor,
        private MessageBusInterface $bus,
        private PdfProcessor $processor,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(IngestPdfCommand $message): void
    {
        $entity = $this->doctrine->getRepository($message->getEntityClass())->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No entity found in IngestPdfHandler', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        /** @var EntityWithFileInfo $entity */
        Assert::isInstanceOf($entity, EntityWithFileInfo::class);
        Assert::true($entity->getFileInfo()->isPaginatable(), 'Entity is not paginatable');

        // Create extractor or cached extractor based on the given options
        $this->extractor->extract($entity, $message->getForceRefresh());
        $result = $this->extractor->getOutput();
        $pageCount = $result?->isSuccessful() ? $result->numberOfPages : 0;

        $this->updatePageCount($entity, $pageCount);

        // Process document and store in elastic as a whole
        $this->processor->processEntity($entity, $message->getForceRefresh());

        // Go and ingest all pages in the document
        for ($i = 1; $i <= $pageCount; $i++) {
            $this->bus->dispatch(
                new IngestPdfPageCommand(
                    $message->getEntityId(),
                    $message->getEntityClass(),
                    $message->getForceRefresh(),
                    $i,
                )
            );
        }
    }

    private function updatePageCount(EntityWithFileInfo $entity, int $pageCount): void
    {
        if ($entity instanceof Document) {
            $entity->setPageCount($pageCount);
        }

        $entity->getFileInfo()->setPageCount($pageCount);

        $this->doctrine->persist($entity);
        $this->doctrine->flush();
    }
}
