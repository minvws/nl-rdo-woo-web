<?php

declare(strict_types=1);

namespace App\Domain\Upload\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Uploader\Event\UploadValidatedEvent;
use App\Domain\Uploader\UploadService;
use App\Service\Storage\EntityStorageService;
use App\Service\Uploader\UploadGroupId;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadValidatedEvent::class, method: 'onUploadValidated')]
final readonly class DocumentUploadHandler
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private UploadService $uploadService,
        private FilesystemOperator $documentStorage,
        private EntityStorageService $entityStorageService,
        private DocumentFileService $documentFileService,
    ) {
    }

    public function onUploadValidated(UploadValidatedEvent $event): void
    {
        $uploadEntity = $event->uploadEntity;
        if ($uploadEntity->getUploadGroupId() !== UploadGroupId::WOO_DECISION_DOCUMENTS) {
            return;
        }

        $wooDecisionId = Uuid::fromString(
            $uploadEntity->getContext()->getString('dossierId'),
        );

        $wooDecision = $this->wooDecisionRepository->findOneByDossierId($wooDecisionId);

        $fileName = $uploadEntity->getFilename();
        Assert::notNull($fileName);

        $size = $uploadEntity->getSize();
        Assert::notNull($size);

        $documentFileSet = $this->documentFileService->getDocumentFileSet($wooDecision);
        $documentFileUpload = $this->documentFileService->createNewUpload($documentFileSet, $fileName);

        $filePath = $this->entityStorageService->generateEntityPath($documentFileUpload, $fileName);
        $this->uploadService->moveUploadToStorage($uploadEntity, $this->documentStorage, $filePath);

        $documentFileUpload->getFileInfo()->setMimetype($uploadEntity->getMimetype());
        $documentFileUpload->getFileInfo()->setSize($size);
        $documentFileUpload->getFileInfo()->setPath($filePath);
        $documentFileUpload->getFileInfo()->setUploaded(true);

        $this->documentFileService->finishUpload($documentFileSet, $documentFileUpload);
    }
}
