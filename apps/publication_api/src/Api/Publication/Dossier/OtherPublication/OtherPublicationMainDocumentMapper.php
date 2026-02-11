<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\OtherPublication;

use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\FileInfo;
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

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->filename);

        $mainDocument->setFileInfo($fileInfo);
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

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->filename);

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
