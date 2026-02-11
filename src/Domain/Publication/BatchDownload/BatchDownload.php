<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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
    private int $downloaded = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $fileCount = 0;

    #[ORM\Column(length: 255, enumType: BatchDownloadStatus::class)]
    private BatchDownloadStatus $status;

    #[ORM\Column(length: 255)]
    private string $filename = '';

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $size = null;

    public function __construct(
        BatchDownloadScope $scope,
        #[ORM\Column]
        private DateTimeImmutable $expiration,
    ) {
        $this->id = Uuid::v6();
        $this->status = BatchDownloadStatus::PENDING;

        if ($scope->wooDecision instanceof WooDecision) {
            $this->dossier = $scope->wooDecision;
        }

        if ($scope->inquiry instanceof Inquiry) {
            $this->inquiry = $scope->inquiry;
        }

        if ($this->dossier === null && $this->inquiry === null) {
            throw new RuntimeException('A BatchDownload entity needs at least one dossier or inquiry relationship');
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getExpiration(): DateTimeImmutable
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

    public function getSize(): ?int
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

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    public function markAsOutdated(): void
    {
        $this->status = BatchDownloadStatus::OUTDATED;
        $this->expiration = new DateTimeImmutable('+15 minutes');
    }

    public function markAsFailed(): void
    {
        $this->status = BatchDownloadStatus::FAILED;
        $this->expiration = new DateTimeImmutable('+15 minutes');
        $this->size = 0;
    }

    public function complete(string $filename, int $size, int $fileCount): void
    {
        $this->status = BatchDownloadStatus::COMPLETED;
        $this->filename = $filename;
        $this->size = $size;
        $this->fileCount = $fileCount;
    }

    public function canBeDownloaded(): bool
    {
        return ($this->status->isCompleted() || $this->status->isOutdated())
            && $this->expiration > new DateTimeImmutable()
            && $this->fileCount > 0;
    }
}
