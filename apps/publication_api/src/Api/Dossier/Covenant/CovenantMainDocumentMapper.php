<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\FileInfo;
use Webmozart\Assert\Assert;

class CovenantMainDocumentMapper
{
    public static function create(
        Covenant $covenant,
        CovenantMainDocumentRequestDto $mainDocumentRequestDto,
    ): CovenantMainDocument {
        $mainDocument = new CovenantMainDocument(
            $covenant,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->language,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName->toString());

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        Covenant $covenant,
        CovenantMainDocumentRequestDto $mainDocumentRequestDto,
    ): CovenantMainDocument {
        $mainDocument = $covenant->getMainDocument();
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
