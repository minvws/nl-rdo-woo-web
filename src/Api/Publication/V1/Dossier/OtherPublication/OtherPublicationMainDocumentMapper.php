<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\OtherPublication;

use Shared\Api\Publication\V1\MainDocument\MainDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Webmozart\Assert\Assert;

class OtherPublicationMainDocumentMapper
{
    public static function create(
        OtherPublication $otherPublication,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): OtherPublicationMainDocument {
        $mainDocument = new OtherPublicationMainDocument(
            $otherPublication,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);

        return $mainDocument;
    }

    public static function update(
        OtherPublication $otherPublication,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): OtherPublicationMainDocument {
        $mainDocument = $otherPublication->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
