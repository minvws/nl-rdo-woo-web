<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Doctrine\TimestampableTrait;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileSetRepository;
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

    #[ORM\ManyToOne(targetEntity: WooDecision::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private WooDecision $dossier;

    #[ORM\Column(length: 255, nullable: false, enumType: DocumentFileSetStatus::class)]
    private DocumentFileSetStatus $status;

    /** @var Collection<array-key,DocumentFileUpload> */
    #[ORM\OneToMany(mappedBy: 'documentFileSet', targetEntity: DocumentFileUpload::class)]
    private Collection $uploads;

    /** @var Collection<array-key,DocumentFileUpdate> */
    #[ORM\OneToMany(mappedBy: 'documentFileSet', targetEntity: DocumentFileUpdate::class)]
    private Collection $updates;

    public function __construct(WooDecision $dossier)
    {
        $this->id = Uuid::v6();
        $this->dossier = $dossier;
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
}
