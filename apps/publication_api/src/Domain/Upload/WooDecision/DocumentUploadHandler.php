<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: UploadValidatedEvent::class, method: 'onUploadValidated')]
final readonly class DocumentUploadHandler
{
    public function __construct(private WooDecisionDispatcher $wooDecisionDispatcher)
    {
    }

    public function onUploadValidated(UploadValidatedEvent $event): void
    {
        $uploadEntity = $event->uploadEntity;
        if ($uploadEntity->getUploadGroupId() !== UploadGroupId::API_WOO_DECISION_DOCUMENTS) {
            return;
        }

        $wooDecisionId = Uuid::fromString($uploadEntity->getContext()->getString('dossierId'));
        $this->wooDecisionDispatcher->dispatchProcessUploadedDocumentsCommand($wooDecisionId, $uploadEntity->getId());
    }
}
