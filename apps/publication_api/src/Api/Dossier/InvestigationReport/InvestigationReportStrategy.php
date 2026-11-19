<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<InvestigationReport,InvestigationReportRequestDto>
 */
readonly class InvestigationReportStrategy implements DossierStrategy
{
    public function __construct(
        private DossierPersister $dossierPersister,
        private InvestigationReportRequestMapper $investigationReportRequestMapper,
        private DossierAttachmentSynchronizer $dossierAttachmentSynchronizer,
        private InvestigationReportMainDocumentRequestMapper $investigationReportMainDocumentRequestMapper,
        private DossierMainDocumentOrNoticeSynchronizer $dossierMainDocumentOrNoticeSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return InvestigationReport::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, InvestigationReportRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): InvestigationReport {
        Assert::isInstanceOf($dossierRequestDto, InvestigationReportRequestDto::class);

        $investigationReport = $this->investigationReportRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        $this->dossierMainDocumentOrNoticeSynchronizer->create(
            $investigationReport,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->investigationReportMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->create($investigationReport, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($investigationReport, null, $attachmentEvents);

        return $investigationReport;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, InvestigationReport::class);
        Assert::isInstanceOf($dossierRequestDto, InvestigationReportRequestDto::class);

        $this->investigationReportRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $mainDocumentSnapshot = $this->dossierPersister->snapshot($dossier);

        $this->dossierMainDocumentOrNoticeSynchronizer->update(
            $dossier,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->investigationReportMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($dossier, $mainDocumentSnapshot, $attachmentEvents);
    }
}
