<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Event\AbstractMainDocumentEvent;
use Symfony\Component\Uid\Uuid;

final readonly class IndexMainDocumentCommand
{
    private function __construct(
        public Uuid $uuid,
    ) {
    }

    public static function forMainDocument(AbstractMainDocument $mainDocument): self
    {
        return new self($mainDocument->getId());
    }

    public static function forMainDocumentEvent(AbstractMainDocumentEvent $event): self
    {
        return new self($event->documentId);
    }
}
