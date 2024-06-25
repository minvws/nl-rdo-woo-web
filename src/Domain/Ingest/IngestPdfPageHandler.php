<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Entity\Document;
use App\Service\Worker\PdfProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

/**
 * Ingest a single PDF page into the system.
 */
#[AsMessageHandler]
final readonly class IngestPdfPageHandler
{
    public function __construct(
        private PdfProcessor $processor,
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(IngestPdfPageMessage $message): void
    {
        $entity = $this->doctrine->getRepository($message->getEntityClass())->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No document found for this message', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
                'pageNr' => $message->getPageNr(),
            ]);

            return;
        }

        try {
            // TODO This assert is temp! Will be removed after below services are more generic.
            Assert::isInstanceOf($entity, Document::class);

            $this->processor->processDocumentPage($entity, $message->getPageNr(), $message->getForceRefresh());
        } catch (\Exception $e) {
            $this->logger->error('Error processing document', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
                'pageNr' => $message->getPageNr(),
                'exception' => $e->getMessage(),
            ]);

            // @TODO Do we want to re-throw exception? This is not done for any of the other Ingest*Handler's
            throw $e;
        }
    }
}
