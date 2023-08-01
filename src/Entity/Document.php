<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'class', type: 'string')]
#[ORM\DiscriminatorMap([
    self::CLASS_INVENTORY => Inventory::class,
    self::CLASS_DOCUMENT => Document::class,
    self::CLASS_DECISION => Decision::class,
])]
class Document
{
    public const CLASS_INVENTORY = 'inventory';
    public const CLASS_DOCUMENT = 'document';
    public const CLASS_DECISION = 'decision';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: false)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimetype;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $filepath;

    #[ORM\Column(nullable: false)]
    private int $filesize = 0;

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

    /* The type of the local file on disk. This is mostly a PDF. These are the types that can be ingested by the workers */
    #[ORM\Column(length: 255, nullable: false)]
    private string $fileType;

    /* The type of the original file. This could be a spreadsheet, word document or email */
    #[ORM\Column(length: 255, nullable: false)]
    private string $sourceType;

    #[ORM\Column(length: 255, nullable: false)]
    private string $documentNr;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private \DateTimeInterface $documentDate;

    #[ORM\Column(length: 255, nullable: false)]
    private string $filename;

    #[ORM\Column(nullable: true)]
    private ?int $familyId = null;

    #[ORM\Column(nullable: true)]
    private ?int $documentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $threadId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $judgement = null;

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private array $grounds = [];

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private array $subjects = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $period = null;

    #[ORM\Column(nullable: false)]
    private bool $uploaded = false;

    /** @var Collection|IngestLog[] */
    #[ORM\OneToMany(mappedBy: 'document', targetEntity: IngestLog::class, orphanRemoval: true)]
    private Collection $ingestLogs;

    #[ORM\Column]
    private bool $suspended;

    #[ORM\Column]
    private bool $withdrawn;

    /** @var Collection|Inquiry[] */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'documents')]
    private Collection $inquiries;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
        $this->ingestLogs = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(UUid $uuid): void
    {
        $this->id = $uuid;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(?string $filepath): self
    {
        $this->filepath = $filepath;

        return $this;
    }

    public function getFilesize(): int
    {
        return $this->filesize;
    }

    public function setFilesize(int $filesize): self
    {
        $this->filesize = $filesize;

        return $this;
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

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFamilyId(): ?int
    {
        return $this->familyId;
    }

    public function setFamilyId(int $familyId): self
    {
        $this->familyId = $familyId;

        return $this;
    }

    public function getDocumentId(): ?int
    {
        return $this->documentId;
    }

    public function setDocumentId(int $documentId): self
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function getThreadId(): ?int
    {
        return $this->threadId;
    }

    public function setThreadId(int $threadId): self
    {
        $this->threadId = $threadId;

        return $this;
    }

    public function getJudgement(): ?string
    {
        return $this->judgement;
    }

    public function setJudgement(string $judgement): self
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

    public function setPeriod(string $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function isUploaded(): bool
    {
        return $this->uploaded;
    }

    public function setUploaded(bool $uploaded): self
    {
        $this->uploaded = $uploaded;

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

    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): self
    {
        $this->sourceType = $sourceType;

        return $this;
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

    public function setWithdrawn(bool $withdrawn): self
    {
        $this->withdrawn = $withdrawn;

        return $this;
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
}
