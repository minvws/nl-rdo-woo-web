<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Stringable;
use Webmozart\Assert\Assert;

use function pathinfo;
use function sprintf;

use const PATHINFO_EXTENSION;

/**
 * Its important that the fileName contains the document id so it can be matched later to the correct document.
 * The extension is needed for matching the actuall mime-type with the suggested extension from the meta-data.
 */
final readonly class DocumentFileName implements Stringable
{
    public string $fileName;

    public function __construct(
        Document $document,
    ) {
        $this->fileName = $this->setFileName($document);
    }

    public function __toString(): string
    {
        return $this->fileName;
    }

    private function setFileName(Document $document): string
    {
        $name = $document->getFileInfo()->getName();
        Assert::string($name, 'Document file info name must be a string');

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        Assert::stringNotEmpty($extension, 'Document file name must have an extension');

        $documentId = $document->getDocumentId();
        Assert::string($documentId, 'Document must have a documentId');

        return sprintf('%s.%s', $documentId, $extension);
    }
}
