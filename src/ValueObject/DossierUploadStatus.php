<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Entity\Document;
use App\Entity\Dossier;
use Doctrine\Common\Collections\ReadableCollection;

class DossierUploadStatus
{
    public function __construct(
        private readonly Dossier $dossier
    ) {
    }

    public function getExpectedUploadCount(): int
    {
        return $this->getExpectedDocuments()->count();
    }

    public function getActualUploadCount(): int
    {
        return $this->dossier->getDocuments()->filter(
            /* @phpstan-ignore-next-line */
            static fn (Document $doc): bool => $doc->shouldBeUploaded() && $doc->isUploaded()
        )->count();
    }

    public function isComplete(): bool
    {
        return $this->getMissingDocuments()->count() === 0;
    }

    public function getUploadedDocuments(): ReadableCollection
    {
        return $this->dossier->getDocuments()->filter(
            /* @phpstan-ignore-next-line */
            static fn (Document $doc): bool => $doc->isUploaded()
        );
    }

    public function getExpectedDocuments(): ReadableCollection
    {
        return $this->dossier->getDocuments()->filter(
            /* @phpstan-ignore-next-line */
            static fn (Document $doc): bool => $doc->shouldBeUploaded()
        );
    }

    public function getMissingDocuments(): ReadableCollection
    {
        return $this->dossier->getDocuments()->filter(
            /* @phpstan-ignore-next-line */
            static fn (Document $doc): bool => $doc->shouldBeUploaded() && ! $doc->isUploaded()
        );
    }
}
