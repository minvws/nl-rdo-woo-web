<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Dossier\MainDocumentRequestMapperInterface;
use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Service\EnumHelper;
use Webmozart\Assert\Assert;

/**
 * @implements MainDocumentRequestMapperInterface<Disposition,DispositionMainDocumentRequestDto>
 */
class DispositionMainDocumentRequestMapper implements MainDocumentRequestMapperInterface
{
    public function create(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): DispositionMainDocument {
        Assert::isInstanceOf($dossier, Disposition::class);
        Assert::isInstanceOf($mainDocumentRequestDto, DispositionMainDocumentRequestDto::class);

        $mainDocument = new DispositionMainDocument(
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
    ): DispositionMainDocument {
        Assert::isInstanceOf($dossier, Disposition::class);
        Assert::isInstanceOf($mainDocumentRequestDto, DispositionMainDocumentRequestDto::class);

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
