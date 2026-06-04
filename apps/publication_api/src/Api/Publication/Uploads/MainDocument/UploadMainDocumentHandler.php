<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Uploads\MainDocument;

use ApiPlatform\Validator\Exception\ValidationException;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\MainDocumentUploadProcessor;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class UploadMainDocumentHandler
{
    public function __construct(
        private MainDocumentUploadProcessor $mainDocumentUploadProcessor,
    ) {
    }

    public function handle(AbstractDossier $dossier, AbstractMainDocument $mainDocument, StreamInterface $content): void
    {
        if (! $dossier->getId()->equals($mainDocument->getDossier()->getId())) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No main document found for this dossier'));
        }

        if ($content->getSize() === 0) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No file content provided'));
        }

        $this->mainDocumentUploadProcessor->process($dossier, $mainDocument, $content);
    }
}
