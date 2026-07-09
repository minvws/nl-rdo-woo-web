<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\EntityWithNoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\HasNoticeNotPublicTrait;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Shared\Validator\ExactlyOneOf\ExactlyOneOf;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithMainDocument<ComplaintJudgementMainDocument>
 */
#[ExactlyOneOf(
    properties: ['mainDocument', 'noticeNotPublic'],
    errorPaths: ['document', 'noticeNotPublic'],
    noneMessage: 'dossier.document_or_notice_required',
    multipleMessage: 'dossier.document_and_notice_not_allowed',
    groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ],
)]
#[ORM\Entity(repositoryClass: ComplaintJudgementRepository::class)]
class ComplaintJudgement extends AbstractDossier implements EntityWithMainDocument, EntityWithNoticeNotPublic
{
    /** @use HasMainDocument<ComplaintJudgementMainDocument> */
    use HasMainDocument;

    use HasNoticeNotPublicTrait;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ComplaintJudgementMainDocument::class, cascade: ['remove', 'persist'])]
    #[Assert\Valid(groups: [
        DossierValidationGroup::DECISION->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    private ?ComplaintJudgementMainDocument $document;

    #[Assert\NotNull(
        message: 'date_mandatory',
        groups: [
            DossierValidationGroup::DETAILS->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
    #[PlainDateBeforeOrEqual(
        date: 'today',
        message: 'date_must_not_be_in_future',
        groups: [
            DossierValidationGroup::DETAILS->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
    protected ?PlainDate $dateFrom = null;

    public function __construct()
    {
        parent::__construct();

        $this->document = null;
    }

    #[Override]
    public function setDateFrom(?PlainDate $dateFrom): static
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateFrom;

        return $this;
    }

    public function getType(): DossierType
    {
        return DossierType::COMPLAINT_JUDGEMENT;
    }

    public function getMainDocumentEntityClass(): string
    {
        return ComplaintJudgementMainDocument::class;
    }
}
