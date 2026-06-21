<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\FileInfo;
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

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName->toString());

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        Disposition $disposition,
        DispositionMainDocumentRequestDto $mainDocumentRequestDto,
    ): DispositionMainDocument {
        $mainDocument = $disposition->getMainDocument();
        Assert::notNull($mainDocument);

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName->toString());

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
