<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class FileInfo
{
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimetype = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $path;

    #[ORM\Column(nullable: false)]
    private int $size = 0;

    /* The type of the local file on disk. This is mostly a PDF. These are the types that can be ingested by the workers */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: false)]
    private bool $uploaded = false;

    /* The type of the original file. This could be a spreadsheet, word document or email */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceType = null;

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isUploaded(): bool
    {
        return $this->uploaded;
    }

    public function setUploaded(bool $uploaded): self
    {
        $this->uploaded = $uploaded;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): self
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    public function removeFileProperties(): void
    {
        $this->setMimetype(null);
        $this->setUploaded(false);
        $this->setSize(0);
        $this->setPath(null);
    }
}
