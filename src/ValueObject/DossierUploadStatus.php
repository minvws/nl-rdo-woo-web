<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ReadableCollection;
use Webmozart\Assert\Assert;

readonly class DossierUploadStatus
{
    public function __construct(
        private WooDecision $dossier,
    ) {
    }

    public function getDossier(): WooDecision
    {
        return $this->dossier;
    }

    public function getExpectedUploadCount(): int
    {
        return $this->getExpectedDocuments()->count();
    }

    public function getActualUploadCount(): int
    {
        return $this->dossier->getDocuments()->filter(
            static fn (Document $doc): bool => $doc->shouldBeUploaded() && $doc->isUploaded()
        )->count();
    }

    public function isComplete(): bool
    {
        return $this->getMissingDocuments()->count() === 0;
    }

    /**
     * @return ReadableCollection<array-key,Document>
     */
    public function getUploadedDocuments(): ReadableCollection
    {
        return $this->dossier->getDocuments()->filter(
            static fn (Document $doc): bool => $doc->isUploaded()
        );
    }

    /**
     * @return ReadableCollection<array-key,Document>
     */
    public function getExpectedDocuments(): ReadableCollection
    {
        return $this->dossier->getDocuments()->filter(
            static fn (Document $doc): bool => $doc->shouldBeUploaded()
        );
    }

    /**
     * @return ReadableCollection<array-key,Document>
     */
    public function getMissingDocuments(): ReadableCollection
    {
        return $this->dossier->getDocuments()->filter(
            static fn (Document $doc): bool => $doc->shouldBeUploaded() && ! $doc->isUploaded()
        );
    }

    /**
     * @param string[] $uploadedFilenames
     */
    public function getDocumentsToUpload(array $uploadedFilenames): ReadableCollection
    {
        $docIdsToIgnore = [];
        foreach ($uploadedFilenames as $filename) {
            $docIdsToIgnore[$filename] = 1;
        }

        return $this->getMissingDocuments()->filter(
            static function (Document $doc) use ($docIdsToIgnore): bool {
                return $doc->getDocumentId() !== null
                    && ! array_key_exists($doc->getDocumentId(), $docIdsToIgnore);
            }
        );
    }

    /**
     * @return ReadableCollection<array-key,string>
     */
    public function getMissingDocumentIds(): ReadableCollection
    {
        return $this
            ->getMissingDocuments()
            ->map(static function (Document $document): string {
                $documentId = $document->getDocumentId();
                Assert::string($documentId);

                return $documentId;
            });
    }
}
