<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\Event;

use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractMainDocumentEvent
{
    final public function __construct(
        public Uuid $documentId,
        public Uuid $dossierId,
        public string $filename,
    ) {
    }

    public static function forDocument(AbstractMainDocument $document): static
    {
        return new static(
            $document->getId(),
            $document->getDossier()->getId(),
            $document->getFileInfo()->getName() ?? '',
        );
    }
}
