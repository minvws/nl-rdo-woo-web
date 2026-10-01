<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierRequestMapper<Covenant,CovenantRequestDto>
 */
readonly class CovenantRequestMapper implements DossierRequestMapper
{
    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): Covenant {
        $covenant = new Covenant();
        $covenant->setExternalId($dossierExternalId);
        $covenant->setStatus(DossierStatus::CONCEPT);
        $covenant->setDocumentPrefix($documentPrefix);

        $this->update($covenant, $dossierRequestDto, $organisation, $department, $subject);

        return $covenant;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, Covenant::class);
        Assert::isInstanceOf($dossierRequestDto, CovenantRequestDto::class);

        $dossier->setDateFrom($dossierRequestDto->dateFrom);
        $dossier->setDateTo($dossierRequestDto->dateTo);
        $dossier->setDepartments([$department]);
        $dossier->setDossierNumber($dossierRequestDto->dossierNumber);
        $dossier->setOrganisation($organisation);
        $dossier->setParties($dossierRequestDto->parties);
        $dossier->setPreviousVersionLink($dossierRequestDto->previousVersionLink);
        if (! $dossier->getStatus()->isPublished()) {
            $dossier->setPublicationDate($dossierRequestDto->publicationDate);
        }
        $dossier->setSubject($subject);
        $dossier->setSummary($dossierRequestDto->summary);
        $dossier->setTitle($dossierRequestDto->title);
    }
}
