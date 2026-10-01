<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
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
        private InvestigationReportPersister $investigationReportPersister,
        private InvestigationReportRequestMapper $investigationReportRequestMapper,
        private InvestigationReportAttachmentSynchronizer $investigationReportAttachmentSynchronizer,
        private InvestigationReportMainDocumentSynchronizer $investigationReportMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
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

        if ($dossierRequestDto->mainDocument !== null) {
            $this->investigationReportMainDocumentSynchronizer->create($investigationReport, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($investigationReport, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->investigationReportAttachmentSynchronizer->create($investigationReport, $dossierRequestDto->attachments);

        $this->investigationReportPersister->persist($investigationReport, null, $attachmentEvents);

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

        $investigationReportSnapshot = $this->investigationReportPersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->investigationReportMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->investigationReportMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->investigationReportAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->investigationReportPersister->persist($dossier, $investigationReportSnapshot, $attachmentEvents);
    }

    private static function noticeNotPublic(InvestigationReportRequestDto $investigationReportRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $investigationReportRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
