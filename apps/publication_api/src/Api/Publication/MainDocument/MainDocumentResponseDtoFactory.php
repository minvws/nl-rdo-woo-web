<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\MainDocument;

use PublicationApi\Domain\Upload\MainDocumentUploadStatusService;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

readonly class MainDocumentResponseDtoFactory
{
    public function __construct(
        private MainDocumentUploadStatusService $mainDocumentUploadStatusService,
    ) {
    }

    public function fromEntity(AbstractMainDocument $mainDocument): MainDocumentResponseDto
    {
        return new MainDocumentResponseDto(
            $mainDocument->getId(),
            $mainDocument->getType(),
            $mainDocument->getLanguage(),
            $mainDocument->getFormalDate(),
            $mainDocument->getGrounds(),
            $mainDocument->getFileInfo()->getName(),
            $this->mainDocumentUploadStatusService->getUploadStatus($mainDocument),
        );
    }
}
