<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Document\DocumentFileName;
use PublicationApi\Domain\Upload\DocumentUploadStatusService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\FileHashService;
use Shared\Service\Uploader\UploadGroupId;

readonly class DocumentUploadProcessor
{
    public function __construct(
        private DocumentUploadStatusService $documentUploadStatusService,
        private UploadService $uploadService,
    ) {
    }

    public function process(
        WooDecision $wooDecision,
        Document $document,
        StreamInterface $content,
    ): void {
        if ($this->isAlreadyUploaded($document, $content)) {
            return;
        }

        $streamUpload = new StreamUpload(
            fileName: new DocumentFileName($document),
            stream: $content,
            groupId: UploadGroupId::API_WOO_DECISION_DOCUMENTS,
            additionalParameters: [
                'dossierId' => $wooDecision->getId()->toRfc4122(),
                'documentId' => $document->getId()->toRfc4122(),
            ],
        );

        $this->uploadService->handleUpload($streamUpload);
    }

    private function isAlreadyUploaded(Document $document, StreamInterface $stream): bool
    {
        $documentHash = $document->getFileInfo()->getHash();
        if ($documentHash === null) {
            return false;
        }

        if ($this->documentUploadStatusService->getUploadStatus($document) !== UploadStatus::PROCESSED) {
            return false;
        }

        return $documentHash === FileHashService::calculatePsrStreamHash($stream);
    }
}
