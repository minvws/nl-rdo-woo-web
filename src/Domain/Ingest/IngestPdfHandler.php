<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Entity\Document;
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

    public function __invoke(IngestPdfMessage $message): void
    {
        $entity = $this->doctrine->getRepository($message->getEntityClass())->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No document found for this message', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        // TODO This assert is temp! Will be removed after below services are more generic.
        Assert::isInstanceOf($entity, Document::class);

        // Create extractor or cached extractor based on the given options
        $this->extractor->extract($entity, $message->getForceRefresh());
        $data = $this->extractor->getOutput($entity, 0);
        $pageCount = $data['count'] ?? 0;

        // Update page count of document
        $entity->setPageCount($pageCount);
        $this->doctrine->persist($entity);
        $this->doctrine->flush();

        // Process document and store in elastic as a whole
        $this->processor->processDocument($entity, $message->getForceRefresh());

        // Go and ingest all pages in the document
        for ($i = 1; $i <= $pageCount; $i++) {
            $message = new IngestPdfPageMessage($message->getEntityId(), $message->getEntityClass(), $i, $message->getForceRefresh());
            $this->bus->dispatch($message);
        }
    }
}
