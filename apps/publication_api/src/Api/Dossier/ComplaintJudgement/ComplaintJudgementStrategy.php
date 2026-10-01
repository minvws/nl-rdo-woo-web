<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<ComplaintJudgement,ComplaintJudgementRequestDto>
 */
readonly class ComplaintJudgementStrategy implements DossierStrategy
{
    public function __construct(
        private ComplaintJudgementPersister $complaintJudgementPersister,
        private ComplaintJudgementRequestMapper $complaintJudgementRequestMapper,
        private ComplaintJudgementMainDocumentSynchronizer $complaintJudgementMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return ComplaintJudgement::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, ComplaintJudgementRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): ComplaintJudgement {
        Assert::isInstanceOf($dossierRequestDto, ComplaintJudgementRequestDto::class);

        $complaintJudgement = $this->complaintJudgementRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($dossierRequestDto->mainDocument !== null) {
            $this->complaintJudgementMainDocumentSynchronizer->create($complaintJudgement, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($complaintJudgement, self::noticeNotPublic($dossierRequestDto));
        }

        $this->complaintJudgementPersister->persist($complaintJudgement, null);

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

        $this->complaintJudgementRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $complaintJudgementSnapshot = $this->complaintJudgementPersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->complaintJudgementMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->complaintJudgementMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $this->complaintJudgementPersister->persist($dossier, $complaintJudgementSnapshot);
    }

    private static function noticeNotPublic(ComplaintJudgementRequestDto $complaintJudgementRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $complaintJudgementRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
