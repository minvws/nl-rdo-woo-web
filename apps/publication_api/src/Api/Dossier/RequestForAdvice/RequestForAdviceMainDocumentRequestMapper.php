<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Service\EnumHelper;
use Webmozart\Assert\Assert;

class RequestForAdviceMainDocumentRequestMapper
{
    public static function create(
        RequestForAdvice $requestForAdvice,
        RequestForAdviceMainDocumentRequestDto $mainDocumentRequestDto,
    ): RequestForAdviceMainDocument {
        $mainDocument = new RequestForAdviceMainDocument(
            $requestForAdvice,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));

        return $mainDocument;
    }

    public static function update(
        RequestForAdvice $requestForAdvice,
        RequestForAdviceMainDocumentRequestDto $mainDocumentRequestDto,
    ): RequestForAdviceMainDocument {
        $mainDocument = $requestForAdvice->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
