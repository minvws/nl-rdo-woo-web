<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Doctrine\TimestampableTrait;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Validator as DossierValidator;
use Shared\Domain\Publication\Subject\Subject;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

use function strtolower;

/**
 * @template T of object
 *
 * This is the base class for dossier type entities. It contains only the common properties and relationships.
 */
#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\Table(name: 'dossier')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    DossierType::WOO_DECISION->value => WooDecision::class,
    DossierType::COVENANT->value => Covenant::class,
    DossierType::ANNUAL_REPORT->value => AnnualReport::class,
    DossierType::INVESTIGATION_REPORT->value => InvestigationReport::class,
    DossierType::DISPOSITION->value => Disposition::class,
    DossierType::COMPLAINT_JUDGEMENT->value => ComplaintJudgement::class,
    DossierType::OTHER_PUBLICATION->value => OtherPublication::class,
    DossierType::ADVICE->value => Advice::class,
    DossierType::REQUEST_FOR_ADVICE->value => RequestForAdvice::class,
])]
#[ORM\UniqueConstraint(name: 'dossier_unique_index', columns: ['dossier_nr', 'document_prefix'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['dossierNr', 'documentPrefix'],
    entityClass: AbstractDossier::class,
    groups: [DossierValidationGroup::DETAILS->value],
)]
#[ORM\UniqueConstraint(name: 'dossier_unique_external_id', columns: ['external_id', 'organisation_id'])]
#[UniqueEntity(
    fields: ['externalId', 'organisation'],
    entityClass: AbstractDossier::class,
    ignoreNull: true,
)]
abstract class AbstractDossier
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    protected Uuid $id;

    #[ORM\Column(length: 128, nullable: true, index: true)]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9\-._~]*$/',
        message: 'external_id_invalid_characters',
    )]
    protected ?string $externalId = null;

    #[ORM\Column(length: 50)]
    #[Assert\Length(min: 3, max: 50, groups: [DossierValidationGroup::DETAILS->value])]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/i',
        message: 'use_only_letters_numbers_and_dashes',
        groups: [DossierValidationGroup::DETAILS->value]
    )]
    protected string $dossierNr = '';

    #[ORM\Column(length: 500)]
    #[Assert\Length(min: 2, max: 500, groups: [DossierValidationGroup::DETAILS->value])]
    protected string $title = '';

    #[ORM\Column(length: 255, enumType: DossierStatus::class)]
    protected DossierStatus $status;

    /** @var Collection<array-key,Department> */
    #[ORM\ManyToMany(targetEntity: Department::class)]
    #[ORM\JoinTable(name: 'dossier_department')]
    #[ORM\JoinColumn(name: 'dossier_id', onDelete: 'cascade')]
    #[Assert\Count(
        min: 1,
        minMessage: 'at_least_one_department_required',
        groups: [DossierValidationGroup::DETAILS->value]
    )]
    protected Collection $departments;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[DossierValidator\DateFromConstraint(
        groups: [
            DossierValidationGroup::COVENANT_DETAILS->value,
        ],
    )]
    #[Assert\NotNull(
        message: 'annual_report_year_mandatory',
        groups: [DossierValidationGroup::ANNUAL_REPORT_DETAILS->value],
    )]
    #[Assert\NotNull(
        message: 'date_mandatory',
        groups: [
            DossierValidationGroup::COVENANT_DETAILS->value,
            DossierValidationGroup::INVESTIGATION_REPORT_DETAILS->value,
            DossierValidationGroup::COMPLAINT_JUDGEMENT_DETAILS->value,
            DossierValidationGroup::DISPOSITION_DETAILS->value,
            DossierValidationGroup::OTHER_PUBLICATION_DETAILS->value,
            DossierValidationGroup::ADVICE_DETAILS->value,
            DossierValidationGroup::REQUEST_FOR_ADVICE_DETAILS->value,
        ],
    )]
    #[Assert\LessThanOrEqual(
        value: 'today',
        message: 'date_must_not_be_in_future',
        groups: [
            DossierValidationGroup::INVESTIGATION_REPORT_DETAILS->value,
            DossierValidationGroup::COMPLAINT_JUDGEMENT_DETAILS->value,
            DossierValidationGroup::DISPOSITION_DETAILS->value,
            DossierValidationGroup::OTHER_PUBLICATION_DETAILS->value,
            DossierValidationGroup::ADVICE_DETAILS->value,
            DossierValidationGroup::REQUEST_FOR_ADVICE_DETAILS->value,
        ],
    )]
    protected ?DateTimeImmutable $dateFrom = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'dateFrom',
        message: 'date_to_before_date_from',
        groups: [DossierValidationGroup::DETAILS->value]
    )]
    #[Assert\LessThanOrEqual(
        value: 'today +5 years',
        message: 'date_max_5_year_in_future',
        groups: [DossierValidationGroup::DETAILS->value]
    )]
    protected ?DateTimeImmutable $dateTo = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DECISION->value, DossierValidationGroup::CONTENT->value])]
    #[Assert\Length(min: 1, max: 1000, groups: [DossierValidationGroup::DECISION->value, DossierValidationGroup::CONTENT->value])]
    protected string $summary = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::DETAILS->value])]
    protected string $documentPrefix = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::PUBLICATION->value])]
    protected ?DateTimeImmutable $publicationDate = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    protected bool $completed = false;

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    protected Organisation $organisation;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255, groups: [DossierValidationGroup::DETAILS->value])]
    protected string $internalReference = '';

    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Subject $subject = null;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->status = DossierStatus::NEW;

        $this->departments = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getDossierNr(): string
    {
        return $this->dossierNr;
    }

    public function setDossierNr(string $dossierNr): self
    {
        $this->dossierNr = strtolower($dossierNr);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getStatus(): DossierStatus
    {
        return $this->status;
    }

    public function setStatus(DossierStatus $status): self
    {
        // If the status changes to 'published' update the (planned) publicationDate to actual date/time of publication
        if ($this->status !== $status && $status->isPublished()) {
            $this->setPublicationDate(CarbonImmutable::now());
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<array-key,Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): self
    {
        if (! $this->departments->contains($department)) {
            $this->departments->add($department);
        }

        return $this;
    }

    public function removeDepartment(Department $department): self
    {
        $this->departments->removeElement($department);

        return $this;
    }

    /**
     * @param Department[] $departments
     *
     * @return $this
     */
    public function setDepartments(array $departments): static
    {
        $this->departments->clear();
        $this->departments->add(...$departments);

        return $this;
    }

    public function getDateFrom(): ?DateTimeImmutable
    {
        return $this->dateFrom;
    }

    public function setDateFrom(?DateTimeImmutable $dateFrom): static
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function getDateTo(): ?DateTimeImmutable
    {
        return $this->dateTo;
    }

    public function setDateTo(?DateTimeImmutable $dateTo): static
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDocumentPrefix(): string
    {
        return $this->documentPrefix;
    }

    public function setDocumentPrefix(string $documentPrefix): self
    {
        $this->documentPrefix = $documentPrefix;

        return $this;
    }

    public function getPublicationDate(): ?DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?DateTimeImmutable $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function hasFuturePublicationDate(): bool
    {
        return $this->publicationDate >= new DateTimeImmutable('today midnight');
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): static
    {
        $this->organisation = $organisation;

        return $this;
    }

    public function getInternalReference(): string
    {
        return $this->internalReference;
    }

    public function setInternalReference(string $internalReference): void
    {
        $this->internalReference = $internalReference;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    abstract public function getType(): DossierType;
}
