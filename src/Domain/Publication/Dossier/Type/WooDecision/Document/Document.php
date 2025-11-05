<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\Shared\AbstractPublicationItem;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @SuppressWarnings("PHPMD.TooManyFields")
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 * @SuppressWarnings("PHPMD.ExcessivePublicCount")
 */
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document extends AbstractPublicationItem
{
    /** @var Collection<array-key,WooDecision> */
    #[ORM\ManyToMany(targetEntity: WooDecision::class, inversedBy: 'documents')]
    #[ORM\JoinTable(
        name: 'document_dossier',
        joinColumns: new ORM\JoinColumn(onDelete: 'cascade'),
        inverseJoinColumns: new ORM\JoinColumn(onDelete: 'cascade'),
    )]
    private Collection $dossiers;

    #[ORM\Column(length: 255, nullable: false, index: true)]
    private string $documentNr;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, index: true)]
    private ?\DateTimeImmutable $documentDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $familyId = null;

    #[ORM\Column(length: 170, nullable: true)]
    private ?string $documentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $threadId = null;

    #[ORM\Column(length: 255, nullable: true, enumType: Judgement::class)]
    private ?Judgement $judgement = null;

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private array $grounds = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $period = null;

    #[ORM\Column]
    private bool $suspended = false;

    #[ORM\Column]
    private bool $withdrawn = false;

    #[ORM\Column(length: 255, nullable: true, enumType: DocumentWithdrawReason::class)]
    private ?DocumentWithdrawReason $withdrawReason = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $withdrawExplanation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $withdrawDate = null;

    /** @var Collection<array-key,Inquiry> */
    #[ORM\ManyToMany(targetEntity: Inquiry::class, mappedBy: 'documents')]
    private Collection $inquiries;

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $links = [];

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $remark = null;

    /** @var Collection<int, self> */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'refersTo')]
    private Collection $referredBy;

    /** @var Collection<int, self> */
    #[ORM\JoinTable(name: 'document_referrals')]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'referred_document_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'referredBy', cascade: ['persist'])]
    private Collection $refersTo;

    public function __construct()
    {
        parent::__construct();

        $this->dossiers = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->refersTo = new ArrayCollection();
        $this->referredBy = new ArrayCollection();
        $this->fileInfo->setPaginatable(true);
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

    public function getDocumentDate(): ?\DateTimeImmutable
    {
        return $this->documentDate;
    }

    public function setDocumentDate(?\DateTimeImmutable $documentDate): self
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

        if ($judgement->isNotPublic()) {
            $this->removeWithdrawn();
        }

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
    public function setGrounds(array $grounds): static
    {
        $this->grounds = $grounds;

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
     * @return Collection<array-key,WooDecision>
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    public function addDossier(WooDecision $dossier): self
    {
        if (! $this->dossiers->contains($dossier)) {
            $this->dossiers->add($dossier);
        }

        return $this;
    }

    public function removeDossier(WooDecision $dossier): self
    {
        $this->dossiers->removeElement($dossier);

        return $this;
    }

    public function hasPubliclyAvailableDossier(): bool
    {
        foreach ($this->dossiers as $dossier) {
            if ($dossier->getStatus()->isPubliclyAvailable()) {
                return true;
            }
        }

        return false;
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
     * @return Collection<array-key,Inquiry>
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

    public function getWithdrawReason(): ?DocumentWithdrawReason
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

    public function withdraw(DocumentWithdrawReason $reason, string $explanation): void
    {
        $this->withdrawn = true;
        $this->withdrawReason = $reason;
        $this->withdrawExplanation = $explanation;
        $this->withdrawDate = new \DateTimeImmutable();

        $this->fileInfo->removeFileProperties();
    }

    public function removeWithdrawn(): void
    {
        $this->withdrawn = false;
        $this->withdrawReason = null;
        $this->withdrawExplanation = '';
        $this->withdrawDate = null;
    }

    /**
     * @return Collection<int, self>
     */
    public function getRefersTo(): Collection
    {
        return $this->refersTo;
    }

    public function addReferralTo(Document $document): self
    {
        if (! $this->refersTo->contains($document)) {
            $this->refersTo->add($document);
        }

        return $this;
    }

    public function removeReferralTo(Document $document): self
    {
        $this->refersTo->removeElement($document);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getReferredBy(): Collection
    {
        return $this->referredBy;
    }
}
