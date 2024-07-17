<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\SubType\SubTypeIngester;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class IndexMainDocumentHandler
{
    public function __construct(
        private AbstractMainDocumentRepository $repository,
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
            $this->subTypeIngester->ingest($mainDocument, new IngestOptions());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update main document in elasticsearch', [
                'uuid' => $command->uuid,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
