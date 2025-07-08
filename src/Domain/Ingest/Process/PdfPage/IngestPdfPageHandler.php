<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\PdfPage;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Ingest a single PDF page into the system.
 */
#[AsMessageHandler]
final readonly class IngestPdfPageHandler
{
    public function __construct(
        private PdfPageProcessor $processor,
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(IngestPdfPageCommand $message): void
    {
        $entity = $this->doctrine->getRepository($message->getEntityClass())->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No entity found in IngestPdfPageHandler', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
                'pageNr' => $message->getPageNr(),
            ]);

            return;
        }

        try {
            $this->processor->processPage($entity, $message->getPageNr(), $message->getForceRefresh());
        } catch (\Exception $e) {
            $this->logger->error('Error processing document in IngestPdfPageHandler', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
                'pageNr' => $message->getPageNr(),
                'exception' => $e->getMessage(),
            ]);

            // Rethrowing the exception ensures the message fails and is retried
            throw $e;
        }
    }
}
