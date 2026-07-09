<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
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

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        Covenant $covenant,
        CovenantMainDocumentRequestDto $mainDocumentRequestDto,
    ): CovenantMainDocument {
        $mainDocument = $covenant->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
