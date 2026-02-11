<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentResponseDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class WooDecisionMapper
{
    /**
     * @param array<array-key,WooDecision> $wooDecisions
     *
     * @return list<WooDecisionDto>
     */
    public static function fromEntities(array $wooDecisions): array
    {
        return array_values(array_map(self::fromEntity(...), $wooDecisions));
    }

    public static function fromEntity(WooDecision $wooDecision): WooDecisionDto
    {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $publicationReason = $wooDecision->getPublicationReason();
        Assert::notNull($publicationReason);

        $department = $wooDecision->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new WooDecisionDto(
            $wooDecision->getId(),
            $wooDecision->getExternalId(),
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
            $wooDecision->getDateFrom(),
            $wooDecision->getDateTo(),
            $wooDecision->getDecision(),
            $publicationReason,
            $wooDecision->getPreviewDate(),
            WooDecisionDocumentResponseDto::fromEntities($wooDecision->getDocuments()->toArray()),
        );
    }

    public static function create(
        WooDecisionRequestDto $wooDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): WooDecision {
        $wooDecision = new WooDecision();
        $wooDecision->setExternalId($externalId);
        $wooDecision->setStatus(DossierStatus::NEW);

        return self::update($wooDecision, $wooDecisionRequestDto, $organisation, $department, $subject);
    }

    public static function update(
        WooDecision $wooDecision,
        WooDecisionRequestDto $wooDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): WooDecision {
        $wooDecision->setDateFrom($wooDecisionRequestDto->dossierDateFrom);
        if ($wooDecisionRequestDto->dossierDateTo !== null) {
            $wooDecision->setDateTo($wooDecisionRequestDto->dossierDateTo);
        }
        $wooDecision->setDecision($wooDecisionRequestDto->decision);
        $wooDecision->setDepartments([$department]);
        $wooDecision->setDossierNr($wooDecisionRequestDto->dossierNumber);
        $wooDecision->setDocumentPrefix($wooDecisionRequestDto->prefix);
        $wooDecision->setInternalReference($wooDecisionRequestDto->internalReference);
        $wooDecision->setOrganisation($organisation);
        $wooDecision->setPreviewDate($wooDecisionRequestDto->previewDate);
        $wooDecision->setPublicationDate($wooDecisionRequestDto->publicationDate);
        $wooDecision->setPublicationReason($wooDecisionRequestDto->reason);
        $wooDecision->setSubject($subject);
        $wooDecision->setSummary($wooDecisionRequestDto->summary);
        $wooDecision->setTitle($wooDecisionRequestDto->title);

        return $wooDecision;
    }
}
