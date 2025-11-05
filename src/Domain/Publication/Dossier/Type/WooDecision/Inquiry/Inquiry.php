<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use App\Doctrine\TimestampableTrait;
use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InquiryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Inquiry
{
    use TimestampableTrait;

    public const int CASENUMBER_MIN_LENGTH = 1;
    public const int CASENUMBER_MAX_LENGTH = 255;
    public const string CASENUMBER_REGEX = '/^[a-z0-9-.;\(\) ]+$/i';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: self::CASENUMBER_MIN_LENGTH, max: self::CASENUMBER_MAX_LENGTH)]
    #[Assert\Regex(
        pattern: self::CASENUMBER_REGEX,
        message: 'use_only_letters_numbers_and_dashes',
    )]
    private string $casenr;

    /** @var Collection<array-key,Document> */
    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'inquiries', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'inquiry_document')]
    private Collection $documents;

    /** @var Collection<array-key,WooDecision> */
    #[ORM\ManyToMany(targetEntity: WooDecision::class, inversedBy: 'inquiries', cascade: ['persist'])]
    #[ORM\JoinTable(
        name: 'inquiry_dossier',
        joinColumns: new ORM\JoinColumn(onDelete: 'cascade'),
        inverseJoinColumns: new ORM\JoinColumn(onDelete: 'cascade'),
    )]
    #[ORM\OrderBy(['decisionDate' => 'DESC'])]
    private Collection $dossiers;

    #[ORM\Column(length: 255)]
    private string $token;

    #[ORM\OneToOne(mappedBy: 'inquiry', targetEntity: InquiryInventory::class)]
    private ?InquiryInventory $inventory = null;

    #[ORM\ManyToOne(inversedBy: 'inquiries')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->dossiers = new ArrayCollection();

        $this->token = Uuid::v6()->toBase58();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCasenr(): string
    {
        return $this->casenr;
    }

    public function setCasenr(string $casenr): self
    {
        $this->casenr = $casenr;

        return $this;
    }

    /**
     * @return Collection<array-key,Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): self
    {
        if (! $this->documents->contains($document)) {
            $this->documents->add($document);
            $document->addInquiry($this);
        }

        return $this;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function removeDocument(Document $document): self
    {
        // Document removal for inquiries is disabled as part of #2868:
        // $this->documents->removeElement($document);
        // $document->removeInquiry($this);

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
        $dossier->removeInquiry($this);

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return Collection<array-key,WooDecision>
     */
    public function getScheduledDossiers(): Collection
    {
        return $this->dossiers->filter(
            static fn (WooDecision $dossier) => $dossier->getStatus()->isScheduled()
        );
    }

    public function getInventory(): ?InquiryInventory
    {
        return $this->inventory;
    }

    public function setInventory(?InquiryInventory $inventory): self
    {
        $this->inventory = $inventory;

        return $this;
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
}
