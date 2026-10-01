<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierRequestMapper<ComplaintJudgement,ComplaintJudgementRequestDto>
 */
readonly class ComplaintJudgementRequestMapper implements DossierRequestMapper
{
    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): ComplaintJudgement {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setExternalId($dossierExternalId);
        $complaintJudgement->setStatus(DossierStatus::CONCEPT);
        $complaintJudgement->setDocumentPrefix($documentPrefix);

        $this->update($complaintJudgement, $dossierRequestDto, $organisation, $department, $subject);

        return $complaintJudgement;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, ComplaintJudgement::class);
        Assert::isInstanceOf($dossierRequestDto, ComplaintJudgementRequestDto::class);

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
