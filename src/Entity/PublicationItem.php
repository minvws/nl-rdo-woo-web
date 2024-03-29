<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

abstract class PublicationItem implements EntityWithFileInfo
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    protected Uuid $id;

    #[ORM\Embedded(class: FileInfo::class, columnPrefix: 'file_')]
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

    public function getFileCacheKey(): string
    {
        $fqn = get_class($this);
        $lastBackslash = intval(strrpos($fqn, '\\'));
        $classBasename = substr($fqn, $lastBackslash + 1);

        return $classBasename . '-' . $this->id->toBase58();
    }
}
