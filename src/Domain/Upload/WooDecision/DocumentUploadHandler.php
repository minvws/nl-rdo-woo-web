<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\WooDecision;

use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UploadValidatedEvent::class, method: 'onUploadValidated')]
final readonly class DocumentUploadHandler
{
    public function __construct(private ProcessUploadedDocumentAction $processUploadedDocumentAction)
    {
    }

    public function onUploadValidated(UploadValidatedEvent $event): void
    {
        $uploadEntity = $event->uploadEntity;
        if ($uploadEntity->getUploadGroupId() !== UploadGroupId::WOO_DECISION_DOCUMENTS) {
            return;
        }

        $this->processUploadedDocumentAction->execute($uploadEntity);
    }
}
