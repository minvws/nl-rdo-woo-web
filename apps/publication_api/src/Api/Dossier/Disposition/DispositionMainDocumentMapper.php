<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Webmozart\Assert\Assert;

class DispositionMainDocumentMapper
{
    public static function create(
        Disposition $disposition,
        DispositionMainDocumentRequestDto $mainDocumentRequestDto,
    ): DispositionMainDocument {
        $mainDocument = new DispositionMainDocument(
            $disposition,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        Disposition $disposition,
        DispositionMainDocumentRequestDto $mainDocumentRequestDto,
    ): DispositionMainDocument {
        $mainDocument = $disposition->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
