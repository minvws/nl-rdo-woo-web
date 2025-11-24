<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\Disposition;

use Shared\Api\Publication\V1\MainDocument\MainDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Webmozart\Assert\Assert;

class DispositionMainDocumentMapper
{
    public static function create(
        Disposition $disposition,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): DispositionMainDocument {
        $mainDocument = new DispositionMainDocument(
            $disposition,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);

        return $mainDocument;
    }

    public static function update(
        Disposition $disposition,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): DispositionMainDocument {
        $mainDocument = $disposition->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
