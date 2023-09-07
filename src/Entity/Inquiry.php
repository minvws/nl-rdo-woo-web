<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InquiryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InquiryRepository::class)]
class Inquiry
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $casenr;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection|Document[] */
    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'inquiries', cascade: ['persist'])]
    private Collection $documents;

    /** @var Collection|Dossier[] */
    #[ORM\ManyToMany(targetEntity: Dossier::class, inversedBy: 'inquiries', cascade: ['persist'])]
    private Collection $dossiers;

    #[ORM\Column(length: 255)]
    private string $token;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->dossiers = new ArrayCollection();
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

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function allDocuments(): Collection
    {
        $documents = new ArrayCollection();

        foreach ($this->dossiers as $dossier) {
            foreach ($dossier->getDocuments() as $document) {
                if (! $documents->contains($document)) {
                    $documents->add($document);
                }
            }
        }

        return $documents;
    }
}
