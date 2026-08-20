<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\DraftDecision;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Entity\HasAttachments;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteAttachments;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Shared\Validator\HasOneAttachmentOfTypes\HasOneAttachmentOfTypes;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<DraftDecisionAttachment>
 * @implements EntityWithMainDocument<DraftDecisionMainDocument>
 */
#[ORM\Entity(repositoryClass: DraftDecisionRepository::class)]
#[NoIncompleteAttachments(groups: [
    DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
    DossierValidationGroup::WORKFLOW_PUBLISH->value,
])]
#[HasOneAttachmentOfTypes(
    types: [AttachmentType::REQUEST_FOR_ADVICE, AttachmentType::POLICY_DOCUMENT],
    errorPaths: ['attachment'],
    message: 'draft_decision.request_for_advice_or_policy_document_required',
    groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ],
)]
class DraftDecision extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<DraftDecisionAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<DraftDecisionMainDocument> */
    use HasMainDocument;

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

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: DraftDecisionMainDocument::class, cascade: ['persist', 'remove'])]
    #[Assert\NotBlank(groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    #[Assert\Valid(groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    private ?DraftDecisionMainDocument $document;

    /** @var Collection<array-key,DraftDecisionAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DraftDecisionAttachment::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    #[Assert\Length(min: 1, max: 1000, groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    protected string $summary = '';

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
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
        return DossierType::DRAFT_DECISION;
    }

    public function getAttachmentEntityClass(): string
    {
        return DraftDecisionAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return DraftDecisionMainDocument::class;
    }
}
