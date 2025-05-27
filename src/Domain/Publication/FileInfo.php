<?php

declare(strict_types=1);

namespace App\Domain\Publication;

use App\SourceType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class FileInfo
{
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimetype = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $hash = null;

    #[ORM\Column(type: Types::BIGINT, nullable: false)]
    private string $size = '0';

    /* The type of the local file on disk. This is mostly a PDF. These are the types that can be ingested by the workers */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 1024, nullable: true)]
    #[Assert\Length(min: 1, max: 255, normalizer: 'trim')]
    private ?string $name = null;

    #[ORM\Column(nullable: false)]
    private bool $uploaded = false;

    /* The type of the original file. This could be a spreadsheet, word document or email */
    #[ORM\Column(length: 255, nullable: true, enumType: SourceType::class)]
    private ?SourceType $sourceType = null;

    #[ORM\Column(nullable: true)]
    private ?int $pageCount = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $paginatable = false;

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function getNormalizedMimeType(): string
    {
        return trim($this->getMimetype() ?? '');
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

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getSize(): int
    {
        return intval($this->size);
    }

    public function setSize(int $size): self
    {
        $this->size = strval($size);

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
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

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSourceType(): ?SourceType
    {
        return $this->sourceType;
    }

    public function setSourceType(SourceType $sourceType): self
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): self
    {
        $this->pageCount = $pageCount;

        return $this;
    }

    public function setPaginatable(bool $paginatable): self
    {
        $this->paginatable = $paginatable;

        return $this;
    }

    public function isPaginatable(): bool
    {
        return $this->paginatable;
    }

    public function removeFileProperties(): void
    {
        $this->setMimetype(null);
        $this->setUploaded(false);
        $this->setPath(null);
        $this->setPageCount(null);
    }

    /** @phpstan-assert-if-true !null $this->getPageCount() */
    public function hasPages(): bool
    {
        return $this->pageCount !== null && $this->pageCount > 0;
    }
}
