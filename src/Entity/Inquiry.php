<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use App\Repository\InquiryRepository;
use App\ValueObject\TranslatableMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InquiryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Inquiry implements EntityWithBatchDownload
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $casenr;

    /** @var Collection|Document[] */
    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'inquiries', cascade: ['persist'])]
    private Collection $documents;

    /** @var Collection|Dossier[] */
    #[ORM\ManyToMany(targetEntity: Dossier::class, inversedBy: 'inquiries', cascade: ['persist'])]
    private Collection $dossiers;

    #[ORM\Column(length: 255)]
    private string $token;

    #[ORM\OneToOne(mappedBy: 'inquiry', targetEntity: InquiryInventory::class)]
    private ?InquiryInventory $inventory = null;

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
     * @return Collection|Document[]
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

    public function removeDocument(Document $document): self
    {
        $this->documents->removeElement($document);
        $document->removeInquiry($this);

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

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return Collection|Dossier[]
     */
    public function getPubliclyAvailableDossiers(): Collection
    {
        /** @var Collection|Dossier[] $dossiers */
        $dossiers = $this->dossiers->filter(
            /* @phpstan-ignore-next-line */
            static fn (Dossier $dossier) => $dossier->getStatus() === Dossier::STATUS_PUBLISHED || $dossier->getStatus() === Dossier::STATUS_PREVIEW
        );

        return $dossiers;
    }

    /**
     * @return Collection|Dossier[]
     */
    public function getScheduledDossiers(): Collection
    {
        /** @var Collection|Dossier[] $dossiers */
        $dossiers = $this->dossiers->filter(
            /* @phpstan-ignore-next-line */
            static fn (Dossier $dossier) => $dossier->getStatus() === Dossier::STATUS_SCHEDULED
        );

        return $dossiers;
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

    public function getDownloadFilePrefix(): TranslatableMessage
    {
        return new TranslatableMessage(
            'filename-inquiry-{caseNr}',
            [
                'caseNr' => $this->casenr,
            ]
        );
    }

    public function isAvailableForBatchDownload(): bool
    {
        return true;
    }
}
