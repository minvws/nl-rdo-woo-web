<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\RequestForAdvice;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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
use Symfony\Component\Validator\Constraints as Assert;

use function array_values;

/**
 * @implements EntityWithAttachments<RequestForAdviceAttachment>
 * @implements EntityWithMainDocument<RequestForAdviceMainDocument>
 */
#[ORM\Entity(repositoryClass: RequestForAdviceRepository::class)]
#[NoIncompleteAttachments(groups: [
    DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
    DossierValidationGroup::WORKFLOW_PUBLISH->value,
])]
class RequestForAdvice extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<RequestForAdviceAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<RequestForAdviceMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(targetEntity: RequestForAdviceMainDocument::class, mappedBy: 'dossier', cascade: ['remove', 'persist'])]
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
    private ?RequestForAdviceMainDocument $document;

    /** @var Collection<array-key,RequestForAdviceAttachment> */
    #[ORM\OneToMany(targetEntity: RequestForAdviceAttachment::class, mappedBy: 'dossier', cascade: ['persist'])]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    #[ORM\Column(length: 255)]
    #[Assert\Url(groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    private string $link = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Assert\Count(
        min: 0,
        max: 1,
        groups: [
            DossierValidationGroup::CONTENT->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
    #[Assert\All(
        constraints: [
            new Assert\NotBlank(),
            new Assert\Length(min: 2, max: 100),
        ],
        groups: [
            DossierValidationGroup::CONTENT->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
    private array $advisoryBodies = [];

    #[Assert\NotNull(
        message: 'date_mandatory',
        groups: [
            DossierValidationGroup::DETAILS->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
    #[Assert\LessThanOrEqual(
        value: 'today',
        message: 'date_must_not_be_in_future',
        groups: [
            DossierValidationGroup::DETAILS->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
    protected ?DateTimeImmutable $dateFrom = null;

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
    public function setDateFrom(?DateTimeImmutable $dateFrom): static
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateFrom;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getType(): DossierType
    {
        return DossierType::REQUEST_FOR_ADVICE;
    }

    public function getAttachmentEntityClass(): string
    {
        return RequestForAdviceAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return RequestForAdviceMainDocument::class;
    }

    /**
     * @return list<string>
     */
    public function getAdvisoryBodies(): array
    {
        return $this->advisoryBodies;
    }

    /**
     * @param array<array-key,string> $values
     */
    public function setAdvisoryBodies(array $values): void
    {
        $this->advisoryBodies = array_values($values);
    }
}
