<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class DocumentUploadHandler
{
    public function __construct(
        private WooDecisionDispatcher $wooDecisionDispatcher,
        private UploadContextReader $uploadContextReader,
    ) {
    }

    public function __invoke(UploadValidatedEvent $event): void
    {
        $uploadEntity = $event->uploadEntity;
        if ($uploadEntity->getUploadGroupId() !== UploadGroupId::API_WOO_DECISION_DOCUMENTS) {
            return;
        }

        $wooDecisionId = $this->uploadContextReader->getDossierId($uploadEntity);
        $this->wooDecisionDispatcher->dispatchProcessUploadedDocumentsCommand($wooDecisionId, $uploadEntity->getId());
    }
}
