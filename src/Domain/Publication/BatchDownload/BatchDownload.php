<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload;

use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BatchDownloadRepository::class)]
class BatchDownload
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?WooDecision $dossier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Inquiry $inquiry = null;

    #[ORM\Column]
    private \DateTimeImmutable $expiration;

    #[ORM\Column]
    private int $downloaded = 0;

    #[ORM\Column]
    private int $fileCount = 0;

    #[ORM\Column(length: 255, enumType: BatchDownloadStatus::class)]
    private BatchDownloadStatus $status;

    #[ORM\Column(length: 255)]
    private string $filename = '';

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $size = null;

    public function __construct(BatchDownloadScope $scope, \DateTimeImmutable $expiration)
    {
        $this->id = Uuid::v6();
        $this->status = BatchDownloadStatus::PENDING;
        $this->expiration = $expiration;

        if ($scope->wooDecision instanceof WooDecision) {
            $this->dossier = $scope->wooDecision;
        }

        if ($scope->inquiry instanceof Inquiry) {
            $this->inquiry = $scope->inquiry;
        }

        if ($this->dossier === null && $this->inquiry === null) {
            throw new \RuntimeException('A BatchDownload entity needs at least one dossier or inquiry relationship');
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getExpiration(): \DateTimeImmutable
    {
        return $this->expiration;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    public function getStatus(): BatchDownloadStatus
    {
        return $this->status;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function getDossier(): ?WooDecision
    {
        return $this->dossier;
    }

    public function getInquiry(): ?Inquiry
    {
        return $this->inquiry;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    public function markAsOutdated(): void
    {
        $this->status = BatchDownloadStatus::OUTDATED;
        $this->expiration = new \DateTimeImmutable('+2 hour');
    }

    public function markAsFailed(): void
    {
        $this->status = BatchDownloadStatus::FAILED;
        $this->expiration = new \DateTimeImmutable('+2 hour');
        $this->size = '0';
    }

    public function complete(string $filename, string $size, int $fileCount): void
    {
        $this->status = BatchDownloadStatus::COMPLETED;
        $this->filename = $filename;
        $this->size = $size;
        $this->fileCount = $fileCount;
    }

    public function canBeDownloaded(): bool
    {
        return ($this->status->isCompleted() || $this->status->isOutdated())
            && $this->expiration > new \DateTimeImmutable()
            && $this->fileCount > 0;
    }
}
