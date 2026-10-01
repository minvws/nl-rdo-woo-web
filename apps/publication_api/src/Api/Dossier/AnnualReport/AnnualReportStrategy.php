<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<AnnualReport,AnnualReportRequestDto>
 */
readonly class AnnualReportStrategy implements DossierStrategy
{
    public function __construct(
        private AnnualReportPersister $annualReportPersister,
        private AnnualReportRequestMapper $annualReportRequestMapper,
        private AnnualReportAttachmentSynchronizer $annualReportAttachmentSynchronizer,
        private AnnualReportMainDocumentSynchronizer $annualReportMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return AnnualReport::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, AnnualReportRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): AnnualReport {
        Assert::isInstanceOf($dossierRequestDto, AnnualReportRequestDto::class);

        $annualReport = $this->annualReportRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($dossierRequestDto->mainDocument !== null) {
            $this->annualReportMainDocumentSynchronizer->create($annualReport, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($annualReport, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->annualReportAttachmentSynchronizer->create($annualReport, $dossierRequestDto->attachments);

        $this->annualReportPersister->persist($annualReport, null, $attachmentEvents);

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

        $this->annualReportRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $annualReportSnapshot = $this->annualReportPersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->annualReportMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->annualReportMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->annualReportAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->annualReportPersister->persist($dossier, $annualReportSnapshot, $attachmentEvents);
    }

    private static function noticeNotPublic(AnnualReportRequestDto $annualReportRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $annualReportRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
