<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\MainDocumentRequestMapperInterface;
use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Webmozart\Assert\Assert;

/**
 * @implements MainDocumentRequestMapperInterface<DraftDecision,DraftDecisionMainDocumentRequestDto>
 */
class DraftDecisionMainDocumentRequestMapper implements MainDocumentRequestMapperInterface
{
    public function create(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): DraftDecisionMainDocument {
        Assert::isInstanceOf($dossier, DraftDecision::class);
        Assert::isInstanceOf($mainDocumentRequestDto, DraftDecisionMainDocumentRequestDto::class);

        $mainDocument = new DraftDecisionMainDocument(
            $dossier,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());

        return $mainDocument;
    }

    public function update(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): DraftDecisionMainDocument {
        Assert::isInstanceOf($dossier, DraftDecision::class);
        Assert::isInstanceOf($mainDocumentRequestDto, DraftDecisionMainDocumentRequestDto::class);

        $mainDocument = $dossier->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
