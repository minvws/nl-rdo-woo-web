<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Document;
use App\Message\IngestAudioMessage;
use App\Service\Worker\AudioProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Ingest an audio file into the system.
 */
#[AsMessageHandler]
class IngestAudioHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected AudioProcessor $processor;

    public function __construct(
        AudioProcessor $processor,
        EntityManagerInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->processor = $processor;
    }

    public function __invoke(IngestAudioMessage $message): void
    {
        try {
            $document = $this->doctrine->getRepository(Document::class)->find($message->getUuid());
            if (! $document) {
                // No document found for this message
                $this->logger->warning('No document found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            $this->processor->process($document);
        } catch (\Exception $e) {
            $this->logger->error('Error processing document', [
                'uuid' => $message->getUuid(),
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
