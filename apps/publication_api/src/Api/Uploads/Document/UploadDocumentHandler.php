<?php

declare(strict_types=1);

namespace PublicationApi\Api\Uploads\Document;

use ApiPlatform\Validator\Exception\ValidationException;
use Psr\Http\Message\StreamInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class UploadDocumentHandler
{
    public function __construct(
        private DocumentUploadProcessor $documentUploadProcessor,
    ) {
    }

    public function handle(WooDecision $dossier, Document $document, StreamInterface $content): void
    {
        if ($document->getJudgement() === Judgement::NOT_PUBLIC) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Document is not public'));
        }

        if ($document->isSuspended()) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Document is suspended'));
        }

        if ($document->isWithdrawn()) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Document is withdrawn'));
        }

        if ($content->getSize() === 0) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No file content provided'));
        }

        $this->documentUploadProcessor->process($dossier, $document, $content);
    }
}
