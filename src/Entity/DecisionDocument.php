<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class DecisionDocument implements EntityWithFileInfo
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\OneToOne(inversedBy: 'decisionDocument', targetEntity: Dossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private Dossier $dossier;

    #[Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    private FileInfo $file;

    public function __construct()
    {
        $this->file = new FileInfo();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setDossier(Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getDossier(): Dossier
    {
        return $this->dossier;
    }

    public function getFileInfo(): FileInfo
    {
        return $this->file;
    }

    public function setFileInfo(FileInfo $fileInfo): self
    {
        $this->file = $fileInfo;

        return $this;
    }

    public function getFileCacheKey(): string
    {
        return 'decision-' . $this->id->toBase58();
    }
}
