<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierRequestMapper<DraftDecision,DraftDecisionRequestDto>
 */
readonly class DraftDecisionRequestMapper implements DossierRequestMapper
{
    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): DraftDecision {
        $draftDecision = new DraftDecision();
        $draftDecision->setExternalId($dossierExternalId);
        $draftDecision->setStatus(DossierStatus::CONCEPT);
        $draftDecision->setDocumentPrefix($documentPrefix);

        $this->update($draftDecision, $dossierRequestDto, $organisation, $department, $subject);

        return $draftDecision;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, DraftDecision::class);
        Assert::isInstanceOf($dossierRequestDto, DraftDecisionRequestDto::class);

        $dossier->setDateFrom($dossierRequestDto->dossierDate);
        $dossier->setDepartments([$department]);
        $dossier->setDossierNumber($dossierRequestDto->dossierNumber);
        $dossier->setOrganisation($organisation);
        if (! $dossier->getStatus()->isPublished()) {
            $dossier->setPublicationDate($dossierRequestDto->publicationDate);
        }
        $dossier->setSubject($subject);
        $dossier->setSummary($dossierRequestDto->summary);
        $dossier->setTitle($dossierRequestDto->title);
    }
}
