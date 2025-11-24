<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\SubType;

use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngester;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class IndexMainDocumentHandler
{
    public function __construct(
        private MainDocumentRepository $repository,
        private SubTypeIndexer $subTypeIndexer,
        private LoggerInterface $logger,
        private SubTypeIngester $subTypeIngester,
    ) {
    }

    public function __invoke(IndexMainDocumentCommand $command): void
    {
        try {
            $mainDocument = $this->repository->find($command->uuid);
            if (! $mainDocument instanceof AbstractMainDocument) {
                $this->logger->warning('No main document entity found for IndexMainDocumentCommand', [
                    'uuid' => $command->uuid,
                ]);

                return;
            }

            $this->subTypeIndexer->index($mainDocument);
            $this->subTypeIngester->ingest($mainDocument, new IngestProcessOptions());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update main document in elasticsearch', [
                'uuid' => $command->uuid,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
