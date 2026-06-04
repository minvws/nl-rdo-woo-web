<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Document;

use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Webmozart\Assert\Assert;

readonly class DocumentPrefixDeterminer
{
    public function __construct(
        private DocumentPrefixRepository $documentPrefixRepository,
    ) {
    }

    public function forOrganisation(Organisation $organisation): string
    {
        $documentPrefix = $this->documentPrefixRepository->getAlphabeticallyFirstByOrganisation($organisation);
        Assert::isInstanceOf($documentPrefix, DocumentPrefix::class, 'no document prefix found for organisation');

        return $documentPrefix->getPrefix();
    }
}
