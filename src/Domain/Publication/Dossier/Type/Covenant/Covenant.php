<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Entity\HasAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Validator\DateFromConstraint;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteAttachments;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Symfony\Component\Validator\Constraints as Assert;

use function array_values;

/**
 * @implements EntityWithAttachments<CovenantAttachment>
 * @implements EntityWithMainDocument<CovenantMainDocument>
 */
#[NoIncompleteAttachments(groups: [
    DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
    DossierValidationGroup::WORKFLOW_PUBLISH->value,
])]
#[ORM\Entity(repositoryClass: CovenantRepository::class)]
class Covenant extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<CovenantAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<CovenantMainDocument> */
    use HasMainDocument;

    #[ORM\Column(length: 2048)]
    #[Assert\Url(groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    #[Assert\Length(min: 0, max: 2048, groups: [
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    protected string $previousVersionLink = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Assert\Count(
        min: 2,
        max: 10,
        minMessage: 'min_max_parties',
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
    private array $parties = [];

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: CovenantMainDocument::class, cascade: ['persist', 'remove'])]
    #[Assert\NotBlank(groups: [
        DossierValidationGroup::DECISION->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    #[Assert\Valid(groups: [
        DossierValidationGroup::DECISION->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    private ?CovenantMainDocument $document;

    /** @var Collection<array-key,CovenantAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: CovenantAttachment::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    #[Assert\Length(min: 1, max: 1000, groups: [
        DossierValidationGroup::DECISION->value,
        DossierValidationGroup::CONTENT->value,
        DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
        DossierValidationGroup::WORKFLOW_PUBLISH->value,
    ])]
    protected string $summary = '';

    #[DateFromConstraint(
        groups: [
            DossierValidationGroup::DETAILS->value,
            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
            DossierValidationGroup::WORKFLOW_PUBLISH->value,
        ],
    )]
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

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
        $this->document = null;
    }

    public function getType(): DossierType
    {
        return DossierType::COVENANT;
    }

    public function getPreviousVersionLink(): string
    {
        return $this->previousVersionLink;
    }

    public function setPreviousVersionLink(string $previousVersionLink): void
    {
        $this->previousVersionLink = $previousVersionLink;
    }

    /**
     * @return list<string>
     */
    public function getParties(): array
    {
        return $this->parties;
    }

    /**
     * @param array<array-key,string> $parties
     */
    public function setParties(array $parties): void
    {
        $this->parties = array_values($parties);
    }

    public function getAttachmentEntityClass(): string
    {
        return CovenantAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return CovenantMainDocument::class;
    }
}
