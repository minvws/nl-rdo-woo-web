<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity;

use App\Doctrine\TimestampableTrait;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DocumentFileSetRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DocumentFileSet
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\Column(length: 255, nullable: false, enumType: DocumentFileSetStatus::class)]
    private DocumentFileSetStatus $status;

    /** @var Collection<array-key,DocumentFileUpload> */
    #[ORM\OneToMany(mappedBy: 'documentFileSet', targetEntity: DocumentFileUpload::class, cascade: ['remove'])]
    private Collection $uploads;

    /** @var Collection<array-key,DocumentFileUpdate> */
    #[ORM\OneToMany(mappedBy: 'documentFileSet', targetEntity: DocumentFileUpdate::class, cascade: ['remove'])]
    private Collection $updates;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: WooDecision::class)]
        #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
        private WooDecision $dossier,
    ) {
        $this->id = Uuid::v6();
        $this->status = DocumentFileSetStatus::OPEN_FOR_UPLOADS;
        $this->uploads = new ArrayCollection();
        $this->updates = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDossier(): WooDecision
    {
        return $this->dossier;
    }

    public function getStatus(): DocumentFileSetStatus
    {
        return $this->status;
    }

    public function setStatus(DocumentFileSetStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Collection<array-key,DocumentFileUpload>
     */
    public function getUploads(): Collection
    {
        return $this->uploads;
    }

    /**
     * @return Collection<array-key,DocumentFileUpdate>
     */
    public function getUpdates(): Collection
    {
        return $this->updates;
    }

    public function canConfirm(): bool
    {
        if ($this->status->needsConfirmation()) {
            return true;
        }

        if ($this->status->isProcessingUploads() && $this->dossier->getStatus()->isConcept()) {
            // Skipping directly from processing_uploads to confirmed is allowed for concept dossiers
            return true;
        }

        return false;
    }
}
