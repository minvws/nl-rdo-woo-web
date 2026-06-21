<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\FileInfo;
use Webmozart\Assert\Assert;

class AdviceMainDocumentMapper
{
    public static function create(
        Advice $advice,
        AdviceMainDocumentRequestDto $mainDocumentRequestDto,
    ): AdviceMainDocument {
        $mainDocument = new AdviceMainDocument(
            $advice,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName->toString());

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        Advice $advice,
        AdviceMainDocumentRequestDto $mainDocumentRequestDto,
    ): AdviceMainDocument {
        $mainDocument = $advice->getMainDocument();
        Assert::notNull($mainDocument);

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName->toString());

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
