<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
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
        private DossierPersister $dossierPersister,
        private ComplaintJudgementRequestMapper $complaintJudgementRequestMapper,
        private ComplaintJudgementMainDocumentRequestMapper $complaintJudgementMainDocumentRequestMapper,
        private DossierMainDocumentOrNoticeSynchronizer $dossierMainDocumentOrNoticeSynchronizer,
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

        $this->dossierMainDocumentOrNoticeSynchronizer->create(
            $complaintJudgement,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->complaintJudgementMainDocumentRequestMapper,
        );

        $this->dossierPersister->persist($complaintJudgement, null, []);

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

        $mainDocumentSnapshot = $this->dossierPersister->snapshot($dossier);

        $this->dossierMainDocumentOrNoticeSynchronizer->update(
            $dossier,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->complaintJudgementMainDocumentRequestMapper,
        );

        $this->dossierPersister->persist($dossier, $mainDocumentSnapshot, []);
    }
}
