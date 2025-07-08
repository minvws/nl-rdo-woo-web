<?php

declare(strict_types=1);

namespace App\Domain\Upload\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Upload\Event\UploadValidatedEvent;
use App\Domain\Upload\Process\EntityUploadStorer;
use App\Service\Uploader\UploadGroupId;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadValidatedEvent::class, method: 'onUploadValidated')]
final readonly class DocumentUploadHandler
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentFileService $documentFileService,
        private EntityUploadStorer $uploadStorer,
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

        $documentFileSet = $this->documentFileService->getDocumentFileSet($wooDecision);
        $documentFileUpload = $this->documentFileService->createNewUpload($documentFileSet, $fileName);

        $this->uploadStorer->storeUploadForEntity($uploadEntity, $documentFileUpload);

        $this->documentFileService->finishUpload($documentFileSet, $documentFileUpload);
    }
}
