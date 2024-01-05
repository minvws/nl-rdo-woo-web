<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document implements EntityWithFileInfo
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    // Number of pages for word based documents
    #[ORM\Column(nullable: false)]
    private int $pageCount = 0;

    // Time in seconds of audio or video documents
    #[ORM\Column(nullable: false)]
    private int $duration = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    /** @var Collection|Dossier[] */
    #[ORM\ManyToMany(targetEntity: Dossier::class, inversedBy: 'documents')]
    private Collection $dossiers;

    #[ORM\Column(length: 255, nullable: false)]
    private string $documentNr;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private \DateTimeInterface $documentDate;

    #[ORM\Column(nullable: true)]
    private ?int $familyId = null;

    #[ORM\Column(nullable: true)]
    private ?string $documentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $threadId = null;

    #[ORM\Column(length: 255, nullable: true, enumType: Judgement::class)]
    private ?Judgement $judgement = null;

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private array $grounds = [];

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private array $subjects = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $period = null;

    /** @var Collection|IngestLog[] */
    #[ORM\OneToMany(mappedBy: 'document', targetEntity: IngestLog::class, orphanRemoval: true)]
    private Collection $ingestLogs;

    #[ORM\Column]
    private bool $suspended = false;

    #[ORM\Column]
    private bool $withdrawn = false;

    #[ORM\Column(length: 255, nullable: true, enumType: WithdrawReason::class)]
    private ?WithdrawReason $withdrawReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $withdrawExplanation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $withdrawDate = null;

    /** @var Collection|Inquiry[] */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'documents')]
    private Collection $inquiries;

    #[Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    private FileInfo $fileInfo;

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $links = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remark = null;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
        $this->ingestLogs = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->fileInfo = new FileInfo();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(UUid $uuid): void
    {
        $this->id = $uuid;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function setPageCount(int $pageCount): self
    {
        $this->pageCount = $pageCount;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDocumentNr(): string
    {
        return $this->documentNr;
    }

    public function setDocumentNr(string $documentNr): self
    {
        $this->documentNr = $documentNr;

        return $this;
    }

    public function getDocumentDate(): \DateTimeInterface
    {
        return $this->documentDate;
    }

    public function setDocumentDate(\DateTimeInterface $documentDate): self
    {
        $this->documentDate = $documentDate;

        return $this;
    }

    public function getFamilyId(): ?int
    {
        return $this->familyId;
    }

    public function setFamilyId(?int $familyId): self
    {
        $this->familyId = $familyId;

        return $this;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(string $documentId): self
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function getThreadId(): ?int
    {
        return $this->threadId;
    }

    public function setThreadId(?int $threadId): self
    {
        $this->threadId = $threadId;

        return $this;
    }

    public function getJudgement(): ?Judgement
    {
        return $this->judgement;
    }

    public function setJudgement(Judgement $judgement): self
    {
        $this->judgement = $judgement;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getGrounds(): array
    {
        return array_values($this->grounds);
    }

    /**
     * @param string[] $grounds
     *
     * @return $this
     */
    public function setGrounds(array $grounds): self
    {
        $this->grounds = $grounds;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSubjects(): array
    {
        return array_values($this->subjects);
    }

    /**
     * @param string[] $subjects
     *
     * @return $this
     */
    public function setSubjects(array $subjects): self
    {
        $this->subjects = $subjects;

        return $this;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(?string $period): self
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @return Collection|Dossier[]
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    public function addDossier(Dossier $dossier): self
    {
        if (! $this->dossiers->contains($dossier)) {
            $this->dossiers->add($dossier);
        }

        return $this;
    }

    public function removeDossier(Dossier $dossier): self
    {
        $this->dossiers->removeElement($dossier);

        return $this;
    }

    public function hasPubliclyAvailableDossier(): bool
    {
        foreach ($this->dossiers as $dossier) {
            if ($dossier->getStatus() === Dossier::STATUS_PREVIEW || $dossier->getStatus() === Dossier::STATUS_PUBLISHED) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection|IngestLog[]
     */
    public function getIngestLogs(): Collection
    {
        return $this->ingestLogs;
    }

    public function addIngestLog(IngestLog $ingestLog): self
    {
        if (! $this->ingestLogs->contains($ingestLog)) {
            $this->ingestLogs->add($ingestLog);
            $ingestLog->setDocument($this);
        }

        return $this;
    }

    public function removeIngestLog(IngestLog $ingestLog): self
    {
        $this->ingestLogs->removeElement($ingestLog);

        return $this;
    }

    /**
     * @return array<string, IngestLog[]>
     */
    public function groupedIngestLogs(): array
    {
        $criteria = Criteria::create()
            ->orderBy([
                'createdAt' => Criteria::ASC,
                'event' => Criteria::ASC,
            ])
        ;

        $logs = $this->getIngestLogs()->matching($criteria);

        $curEvent = '';
        $baseTime = null;

        $grouped = [];
        foreach ($logs as $log) {
            if ($curEvent !== $log->getEvent()) {
                $curEvent = $log->getEvent();
                $grouped[$log->getEvent()] = [];
                $baseTime = $log->getCreatedAt();
            }
            $grouped[$log->getEvent()][] = $log;

            if ($baseTime !== null) {
                $log->diff = $baseTime->diff($log->getCreatedAt())->format('%I:%S');
            } else {
                $log->diff = '00:00';
            }
        }

        return $grouped;
    }

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    public function setSuspended(bool $suspended): self
    {
        $this->suspended = $suspended;

        return $this;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn;
    }

    /**
     * @return Collection|Inquiry[]
     */
    public function getInquiries(): Collection
    {
        return $this->inquiries;
    }

    public function addInquiry(Inquiry $inquiry): static
    {
        if (! $this->inquiries->contains($inquiry)) {
            $this->inquiries->add($inquiry);
        }

        return $this;
    }

    public function removeInquiry(Inquiry $inquiry): static
    {
        if ($this->inquiries->removeElement($inquiry)) {
            $inquiry->removeDocument($this);
        }

        return $this;
    }

    public function getFileInfo(): FileInfo
    {
        return $this->fileInfo;
    }

    public function setFileInfo(FileInfo $fileInfo): self
    {
        $this->fileInfo = $fileInfo;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLinks(): array
    {
        return array_values($this->links);
    }

    /**
     * @param string[] $links
     */
    public function setLinks(array $links): static
    {
        $this->links = $links;

        return $this;
    }

    public function isUploaded(): bool
    {
        return $this->fileInfo->isUploaded();
    }

    public function shouldBeUploaded(bool $ignoreWithdrawn = false): bool
    {
        if ($this->suspended === true) {
            return false;
        }

        if ($ignoreWithdrawn === false && $this->withdrawn === true) {
            return false;
        }

        if (! $this->judgement) {
            return false;
        }

        return $this->judgement->isAtLeastPartialPublic();
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): static
    {
        $this->remark = $remark;

        return $this;
    }

    public function getFileCacheKey(): string
    {
        return $this->documentNr;
    }

    public function getWithdrawReason(): ?WithdrawReason
    {
        return $this->withdrawReason;
    }

    public function getWithdrawExplanation(): ?string
    {
        return $this->withdrawExplanation;
    }

    public function getWithdrawDate(): ?\DateTimeImmutable
    {
        return $this->withdrawDate;
    }

    public function withdraw(WithdrawReason $reason, string $explanation): void
    {
        $this->withdrawn = true;
        $this->withdrawReason = $reason;
        $this->withdrawExplanation = $explanation;
        $this->withdrawDate = new \DateTimeImmutable();

        $this->fileInfo->removeFileProperties();
    }

    public function republish(): void
    {
        $this->withdrawn = false;
        $this->withdrawReason = null;
        $this->withdrawExplanation = '';
        $this->withdrawDate = null;
    }
}
