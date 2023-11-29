<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BatchDownloadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BatchDownloadRepository::class)]
class BatchDownload
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Dossier $dossier;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Inquiry $inquiry;

    #[ORM\Column]
    private \DateTimeImmutable $expiration;

    #[ORM\Column]
    private int $downloaded = 0;

    /** @var string[] */
    #[ORM\Column]
    private array $documents;

    #[ORM\Column(length: 255)]
    private string $status;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $size = null;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(UUid $uuid): void
    {
        $this->id = $uuid;
    }

    public function getExpiration(): \DateTimeImmutable
    {
        return $this->expiration;
    }

    public function setExpiration(\DateTimeImmutable $expiration): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    public function setDownloaded(int $downloaded): static
    {
        $this->downloaded = $downloaded;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * @param string[] $documents
     *
     * @return $this
     */
    public function setDocuments(array $documents): static
    {
        $this->documents = $documents;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getEntity(): EntityWithBatchDownload
    {
        if ($this->dossier instanceof EntityWithBatchDownload) {
            return $this->dossier;
        }

        if ($this->inquiry instanceof EntityWithBatchDownload) {
            return $this->inquiry;
        }

        throw new \RuntimeException('Batchdownload has no entity relation');
    }

    public function setEntity(EntityWithBatchDownload $entity): void
    {
        if ($entity instanceof Dossier) {
            $this->dossier = $entity;

            return;
        }

        if ($entity instanceof Inquiry) {
            $this->inquiry = $entity;

            return;
        }

        throw new \RuntimeException('Batchdownload does not support this entity type');
    }

    public function getFilename(): string
    {
        return $this->filename ?? 'download-' . $this->id->toBase58() . '.zip';
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }
}
