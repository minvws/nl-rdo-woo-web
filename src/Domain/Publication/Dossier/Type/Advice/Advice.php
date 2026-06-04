<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Advice;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Entity\HasAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteAttachments;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<AdviceAttachment>
 * @implements EntityWithMainDocument<AdviceMainDocument>
 */
#[ORM\Entity(repositoryClass: AdviceRepository::class)]
#[NoIncompleteAttachments(groups: [
    DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
    DossierValidationGroup::WORKFLOW_PUBLISH->value,
])]
class Advice extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<AdviceAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<AdviceMainDocument> */
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

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: AdviceMainDocument::class, cascade: ['persist', 'remove'])]
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
    private ?AdviceMainDocument $document;

    /** @var Collection<array-key,AdviceAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: AdviceAttachment::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    #[Assert\Length(min: 1, max: 1000, groups: [
        DossierValidationGroup::DECISION->value,
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
        return DossierType::ADVICE;
    }

    public function getAttachmentEntityClass(): string
    {
        return AdviceAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return AdviceMainDocument::class;
    }
}
