<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class IndexAttachmentHandler
{
    public function __construct(
        private AttachmentRepository $repository,
        private SubTypeIndexer $subTypeIndexer,
        private LoggerInterface $logger,
        private SubTypeIngester $subTypeIngester,
    ) {
    }

    public function __invoke(IndexAttachmentCommand $command): void
    {
        try {
            $attachment = $this->repository->find($command->uuid);
            if (! $attachment instanceof AbstractAttachment) {
                $this->logger->warning('No attachment entity found for IndexAttachmentCommand', [
                    'uuid' => $command->uuid,
                ]);

                return;
            }

            $this->subTypeIndexer->index($attachment);
            $this->subTypeIngester->ingest($attachment, new IngestProcessOptions());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update attachment in elasticsearch', [
                'uuid' => $command->uuid,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
