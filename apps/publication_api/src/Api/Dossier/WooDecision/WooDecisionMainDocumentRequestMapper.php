<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\MainDocumentRequestMapperInterface;
use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\EnumHelper;
use Webmozart\Assert\Assert;

/**
 * @implements MainDocumentRequestMapperInterface<WooDecision,WooDecisionMainDocumentRequestDto>
 */
class WooDecisionMainDocumentRequestMapper implements MainDocumentRequestMapperInterface
{
    public function create(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): WooDecisionMainDocument {
        Assert::isInstanceOf($dossier, WooDecision::class);
        Assert::isInstanceOf($mainDocumentRequestDto, WooDecisionMainDocumentRequestDto::class);

        $mainDocument = new WooDecisionMainDocument(
            $dossier,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));

        return $mainDocument;
    }

    public function update(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): WooDecisionMainDocument {
        Assert::isInstanceOf($dossier, WooDecision::class);
        Assert::isInstanceOf($mainDocumentRequestDto, WooDecisionMainDocumentRequestDto::class);

        $mainDocument = $dossier->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
