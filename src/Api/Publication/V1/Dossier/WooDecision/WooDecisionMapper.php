<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\WooDecision;

use Shared\Api\Publication\V1\Attachment\AttachmentResponseDto;
use Shared\Api\Publication\V1\Department\DepartmentReferenceDto;
use Shared\Api\Publication\V1\MainDocument\MainDocumentResponseDto;
use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class WooDecisionMapper
{
    /**
     * @param array<array-key,WooDecision> $wooDecisions
     *
     * @return array<array-key,WooDecisionDto>
     */
    public static function fromEntities(array $wooDecisions): array
    {
        return array_map(fn (WooDecision $wooDecision): WooDecisionDto => self::fromEntity($wooDecision), $wooDecisions);
    }

    public static function fromEntity(WooDecision $wooDecision): WooDecisionDto
    {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $wooDecision->getDateFrom();
        Assert::notNull($dateFrom);

        $publicationReason = $wooDecision->getPublicationReason();
        Assert::notNull($publicationReason);

        $previewDate = $wooDecision->getPreviewDate();
        Assert::notNull($previewDate);

        $department = $wooDecision->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        $decision = $wooDecision->getDecision();
        Assert::notNull($decision);

        return new WooDecisionDto(
            $wooDecision->getId(),
            OrganisationReferenceDto::fromEntity($wooDecision->getOrganisation()),
            $wooDecision->getDocumentPrefix(),
            $wooDecision->getDossierNr(),
            $wooDecision->getInternalReference(),
            $wooDecision->getTitle(),
            $wooDecision->getSummary(),
            $wooDecision->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $wooDecision->getPublicationDate(),
            $wooDecision->getStatus(),
            $mainDocumentDto,
            AttachmentResponseDto::fromEntities($wooDecision->getAttachments()->toArray()),
            $dateFrom,
            $wooDecision->getDateTo(),
            $decision,
            $publicationReason,
            $previewDate,
        );
    }

    public static function create(
        WooDecisionCreateRequestDto $wooDecisionCreateRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): WooDecision {
        $wooDecision = new WooDecision();
        $wooDecision->setDateFrom($wooDecisionCreateRequestDto->dossierDateFrom);
        if ($wooDecisionCreateRequestDto->dossierDateTo !== null) {
            $wooDecision->setDateTo($wooDecisionCreateRequestDto->dossierDateTo);
        }
        $wooDecision->setDecision($wooDecisionCreateRequestDto->decision);
        $wooDecision->setDepartments([$department]);
        $wooDecision->setDocumentPrefix($wooDecisionCreateRequestDto->prefix);
        $wooDecision->setDossierNr($wooDecisionCreateRequestDto->dossierNumber);
        $wooDecision->setInternalReference($wooDecisionCreateRequestDto->internalReference);
        $wooDecision->setOrganisation($organisation);
        $wooDecision->setPreviewDate($wooDecisionCreateRequestDto->previewDate);
        $wooDecision->setPublicationDate($wooDecisionCreateRequestDto->publicationDate);
        $wooDecision->setPublicationReason($wooDecisionCreateRequestDto->reason);
        $wooDecision->setStatus(DossierStatus::NEW);
        $wooDecision->setSubject($subject);
        $wooDecision->setSummary($wooDecisionCreateRequestDto->summary);
        $wooDecision->setTitle($wooDecisionCreateRequestDto->title);

        return $wooDecision;
    }

    public static function update(
        WooDecision $wooDecision,
        WooDecisionUpdateRequestDto $wooDecisionUpdateRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): WooDecision {
        $wooDecision->setDateFrom($wooDecisionUpdateRequestDto->dossierDateFrom);
        if ($wooDecisionUpdateRequestDto->dossierDateTo !== null) {
            $wooDecision->setDateTo($wooDecisionUpdateRequestDto->dossierDateTo);
        }
        $wooDecision->setDecision($wooDecisionUpdateRequestDto->decision);
        $wooDecision->setDepartments([$department]);
        $wooDecision->setDossierNr($wooDecisionUpdateRequestDto->dossierNumber);
        $wooDecision->setDocumentPrefix($wooDecisionUpdateRequestDto->prefix);
        $wooDecision->setInternalReference($wooDecisionUpdateRequestDto->internalReference);
        $wooDecision->setOrganisation($organisation);
        $wooDecision->setPreviewDate($wooDecisionUpdateRequestDto->previewDate);
        $wooDecision->setPublicationDate($wooDecisionUpdateRequestDto->publicationDate);
        $wooDecision->setPublicationReason($wooDecisionUpdateRequestDto->reason);
        $wooDecision->setSubject($subject);
        $wooDecision->setSummary($wooDecisionUpdateRequestDto->summary);
        $wooDecision->setTitle($wooDecisionUpdateRequestDto->title);

        return $wooDecision;
    }
}
