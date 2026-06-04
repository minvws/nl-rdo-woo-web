<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentResponseDtoFactory;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class WooDecisionMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private WooDecisionDocumentResponseDtoFactory $wooDecisionDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,WooDecision> $wooDecisions
     *
     * @return list<WooDecisionResponseDto>
     */
    public function fromEntities(array $wooDecisions): array
    {
        return array_values(array_map(self::fromEntity(...), $wooDecisions));
    }

    public function fromEntity(WooDecision $wooDecision): WooDecisionResponseDto
    {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = $this->mainDocumentResponseDtoFactory->fromEntity($mainDocument);

        $publicationReason = $wooDecision->getPublicationReason();
        Assert::notNull($publicationReason);

        $department = $wooDecision->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new WooDecisionResponseDto(
            $wooDecision->getId(),
            $wooDecision->getExternalId(),
            OrganisationReferenceDto::fromEntity($wooDecision->getOrganisation()),
            $wooDecision->getDossierNr(),
            $wooDecision->getTitle(),
            $wooDecision->getSummary(),
            $wooDecision->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $wooDecision->getPublicationDate(),
            $wooDecision->getStatus(),
            $mainDocumentDto,
            $this->attachmentResponseDtoFactory->fromEntities($wooDecision->getAttachments()->toArray()),
            $wooDecision->getDateFrom(),
            $wooDecision->getDateTo(),
            $wooDecision->getDecision(),
            $publicationReason,
            $wooDecision->getPreviewDate(),
            $this->wooDecisionDocumentResponseDtoFactory->fromEntities($wooDecision->getDocuments()->toArray()),
        );
    }

    public static function create(
        WooDecisionRequestDto $wooDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): WooDecision {
        $wooDecision = new WooDecision();
        $wooDecision->setExternalId($externalId);
        $wooDecision->setStatus(DossierStatus::NEW);
        $wooDecision->setDocumentPrefix($documentPrefix);

        return self::update($wooDecision, $wooDecisionRequestDto, $organisation, $department, $subject);
    }

    public static function update(
        WooDecision $wooDecision,
        WooDecisionRequestDto $wooDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): WooDecision {
        $wooDecision->setDateFrom($wooDecisionRequestDto->dateFrom);
        if ($wooDecisionRequestDto->dateTo !== null) {
            $wooDecision->setDateTo($wooDecisionRequestDto->dateTo);
        }
        $wooDecision->setDecision($wooDecisionRequestDto->decision);
        $wooDecision->setDepartments([$department]);
        $wooDecision->setDossierNr($wooDecisionRequestDto->dossierNumber);
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
