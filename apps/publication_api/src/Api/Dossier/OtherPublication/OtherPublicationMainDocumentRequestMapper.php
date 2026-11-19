<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Dossier\MainDocumentRequestMapperInterface;
use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Service\EnumHelper;
use Webmozart\Assert\Assert;

/**
 * @implements MainDocumentRequestMapperInterface<OtherPublication,OtherPublicationMainDocumentRequestDto>
 */
class OtherPublicationMainDocumentRequestMapper implements MainDocumentRequestMapperInterface
{
    public function create(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): OtherPublicationMainDocument {
        Assert::isInstanceOf($dossier, OtherPublication::class);
        Assert::isInstanceOf($mainDocumentRequestDto, OtherPublicationMainDocumentRequestDto::class);

        $mainDocument = new OtherPublicationMainDocument(
            $dossier,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));

        return $mainDocument;
    }

    public function update(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): OtherPublicationMainDocument {
        Assert::isInstanceOf($dossier, OtherPublication::class);
        Assert::isInstanceOf($mainDocumentRequestDto, OtherPublicationMainDocumentRequestDto::class);

        $mainDocument = $dossier->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
