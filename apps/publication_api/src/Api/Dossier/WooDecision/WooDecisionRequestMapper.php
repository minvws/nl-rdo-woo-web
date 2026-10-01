<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierRequestMapper<WooDecision,WooDecisionRequestDto>
 */
readonly class WooDecisionRequestMapper implements DossierRequestMapper
{
    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): WooDecision {
        $wooDecision = new WooDecision();
        $wooDecision->setExternalId($dossierExternalId);
        $wooDecision->setStatus(DossierStatus::CONCEPT);
        $wooDecision->setDocumentPrefix($documentPrefix);

        $this->update($wooDecision, $dossierRequestDto, $organisation, $department, $subject);

        return $wooDecision;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, WooDecision::class);
        Assert::isInstanceOf($dossierRequestDto, WooDecisionRequestDto::class);

        $dossier->setDateFrom($dossierRequestDto->dateFrom);
        if ($dossierRequestDto->dateTo !== null) {
            $dossier->setDateTo($dossierRequestDto->dateTo);
        }
        $dossier->setDecision($dossierRequestDto->decision);
        $dossier->setDepartments([$department]);
        $dossier->setDossierNumber($dossierRequestDto->dossierNumber);
        $dossier->setOrganisation($organisation);
        if (! $dossier->getStatus()->isPublished()) {
            $dossier->setPreviewDate($dossierRequestDto->previewDate);
            $dossier->setPublicationDate($dossierRequestDto->publicationDate);
        }
        $dossier->setPublicationReason($dossierRequestDto->reason);
        $dossier->setSubject($subject);
        $dossier->setSummary($dossierRequestDto->summary);
        $dossier->setTitle($dossierRequestDto->title);
    }
}
