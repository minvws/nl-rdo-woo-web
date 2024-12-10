<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Doctrine\FileCacheKeyBasedOnClassAndIdTrait;
use App\Doctrine\TimestampableTrait;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\FileInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractPublicationItem implements EntityWithFileInfo
{
    use TimestampableTrait;
    use FileCacheKeyBasedOnClassAndIdTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    protected Uuid $id;

    #[ORM\Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    #[Assert\Valid()]
    protected FileInfo $fileInfo;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->fileInfo = new FileInfo();
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
}
