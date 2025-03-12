<?php

declare(strict_types=1);

namespace App\Domain\Upload\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Upload\UploadedFile;
use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadEvents::POST_UPLOAD . '.woo_decision', method: 'onPostUpload')]
final readonly class DocumentPostUpload
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentFileService $documentFileService,
    ) {
    }

    public function onPostUpload(PostUploadEvent $event): void
    {
        $uploaderGroupId = UploadGroupId::from($event->getRequest()->getPayload()->getString('groupId'));
        if ($uploaderGroupId !== UploadGroupId::WOO_DECISION_DOCUMENTS) {
            return;
        }

        $wooDecisionId = Uuid::fromString($event->getRequest()->attributes->getString('dossierId'));

        $wooDecision = $this->wooDecisionRepository->findOneByDossierId($wooDecisionId);

        $file = $event->getRequest()->files->get('file');
        Assert::isInstanceOf($file, SymfonyUploadedFile::class);

        $uploadedFile = new UploadedFile($event->getFile()->getPathname(), $file->getClientOriginalName());

        $this->documentFileService->addUpload($wooDecision, $uploadedFile);
    }
}
