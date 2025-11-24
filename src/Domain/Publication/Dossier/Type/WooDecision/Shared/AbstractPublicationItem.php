<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Shared;

use Doctrine\ORM\Mapping as ORM;
use Shared\Doctrine\FileCacheKeyBasedOnClassAndIdTrait;
use Shared\Doctrine\TimestampableTrait;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
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
