<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Webmozart\Assert\Assert;

use function sprintf;

/**
 * @implements DossierRequestMapper<AnnualReport,AnnualReportRequestDto>
 */
readonly class AnnualReportRequestMapper implements DossierRequestMapper
{
    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): AnnualReport {
        $annualReport = new AnnualReport();
        $annualReport->setExternalId($dossierExternalId);
        $annualReport->setStatus(DossierStatus::CONCEPT);
        $annualReport->setDocumentPrefix($documentPrefix);

        $this->update($annualReport, $dossierRequestDto, $organisation, $department, $subject);

        return $annualReport;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, AnnualReport::class);
        Assert::isInstanceOf($dossierRequestDto, AnnualReportRequestDto::class);

        $dossier->setDateFrom(PlainDate::createFromFormat('Y-m-d', sprintf('%d-01-01', $dossierRequestDto->year)));
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
