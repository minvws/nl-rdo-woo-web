<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Webmozart\Assert\Assert;

class OtherPublicationMainDocumentMapper
{
    public static function create(
        OtherPublication $otherPublication,
        OtherPublicationMainDocumentRequestDto $mainDocumentRequestDto,
    ): OtherPublicationMainDocument {
        $mainDocument = new OtherPublicationMainDocument(
            $otherPublication,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        OtherPublication $otherPublication,
        OtherPublicationMainDocumentRequestDto $mainDocumentRequestDto,
    ): OtherPublicationMainDocument {
        $mainDocument = $otherPublication->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
