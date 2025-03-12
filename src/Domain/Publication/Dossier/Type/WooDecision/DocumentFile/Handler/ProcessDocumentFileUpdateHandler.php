<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use App\Domain\Upload\Postprocessor\Strategy\FileStrategy;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class ProcessDocumentFileUpdateHandler
{
    public function __construct(
        private DocumentFileUpdateRepository $documentFileUpdateRepository,
        private LoggerInterface $logger,
        private EntityStorageService $entityStorageService,
        private DocumentFileService $documentFileService,
        private FileStrategy $fileStrategy,
    ) {
    }

    public function __invoke(ProcessDocumentFileUpdateCommand $command): void
    {
        $documentFileUpdate = $this->documentFileUpdateRepository->find($command->id);
        if ($documentFileUpdate === null) {
            $this->logger->warning('No DocumentFileUpdate found for this command', [
                'id' => $command->id,
            ]);

            return;
        }

        if (! $documentFileUpdate->getStatus()->isPending()) {
            return;
        }

        $localFile = $this->entityStorageService->downloadEntity($documentFileUpdate);
        if ($localFile === false) {
            $this->logger->warning('No file could be downloaded for DocumentFileUpdate', [
                'id' => $command->id,
            ]);

            return;
        }

        $documentId = $documentFileUpdate->getDocument()->getDocumentId();
        Assert::notNull($documentId);

        $this->fileStrategy->process(
            new UploadedFile($localFile),
            $documentFileUpdate->getDocumentFileSet()->getDossier(),
            $documentId,
            $documentFileUpdate->getFileInfo()->getType(),
        );

        $documentFileUpdate->setStatus(DocumentFileUpdateStatus::COMPLETED);
        $this->documentFileUpdateRepository->save($documentFileUpdate, true);

        $this->documentFileService->checkProcessingUpdatesCompletion($documentFileUpdate->getDocumentFileSet());

        // Remove the upload file as this has now been 'forwarded' to the Document entity
        $this->entityStorageService->deleteAllFilesForEntity($documentFileUpdate);

        $this->entityStorageService->removeDownload($localFile, true);
    }
}
