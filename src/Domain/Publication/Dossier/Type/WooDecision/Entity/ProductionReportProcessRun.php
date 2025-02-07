<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Domain\Publication\Dossier\Type\WooDecision\Repository\ProductionReportProcessRunRepository;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\FileInfo;
use App\Exception\TranslatableException;
use App\Service\Inventory\InventoryChangeset;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[ORM\Entity(repositoryClass: ProductionReportProcessRunRepository::class)]
class ProductionReportProcessRun implements EntityWithFileInfo
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPARING = 'comparing';
    public const STATUS_NEEDS_CONFIRMATION = 'needs_confirmation';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_UPDATING = 'updating';
    public const STATUS_FAILED = 'failed';
    public const STATUS_FINISHED = 'finished';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\OneToOne(inversedBy: 'processRun', targetEntity: WooDecision::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private WooDecision $dossier;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endedAt;

    /** @var array<int, array{message: string, translation: string, placeholders: array<string, string>}> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private array $genericErrors = [];

    /** @var array<int, array<int, array{message: string, translation: string, placeholders: array<string, string>}>> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private array $rowErrors = [];

    /** @var array<string, string> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $changeset;

    #[ORM\Column(length: 255)]
    private string $status;

    #[ORM\Column(type: 'smallint', nullable: false)]
    private int $progress;

    #[Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    private FileInfo $file;

    private ?string $tmpFilename = null;

    public function __construct(WooDecision $dossier)
    {
        $this->dossier = $dossier;
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_PENDING;
        $this->progress = 0;
        $this->file = new FileInfo();

        $dossier->setProcessRun($this);
    }

    public function startComparing(): self
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \RuntimeException('Can only start an production report comparison when the run is pending');
        }

        $this->status = self::STATUS_COMPARING;
        $this->startedAt = new \DateTimeImmutable();

        return $this;
    }

    public function startUpdating(): self
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            throw new \RuntimeException('Can only start production report updating when the run is confirmed');
        }

        $this->status = self::STATUS_UPDATING;
        $this->startedAt = new \DateTimeImmutable();

        return $this;
    }

    public function addGenericException(TranslatableException $exception): self
    {
        if ($this->isFinal()) {
            throw new \RuntimeException('Cannot add errors to a run in a final state');
        }

        $this->genericErrors[] = [
            'message' => $exception->getMessage(),
            'translation' => $exception->getTranslationKey(),
            'placeholders' => $exception->getPlaceholders(),
        ];

        return $this;
    }

    public function addRowException(int $rowNumber, TranslatableException $exception): self
    {
        if ($this->isFinal()) {
            throw new \RuntimeException('Cannot add errors to a run in a final state');
        }

        if (! array_key_exists($rowNumber, $this->rowErrors)) {
            $this->rowErrors[$rowNumber] = [];
        }

        $this->rowErrors[$rowNumber][] = [
            'message' => $exception->getMessage(),
            'translation' => $exception->getTranslationKey(),
            'placeholders' => $exception->getPlaceholders(),
        ];

        return $this;
    }

    public function finish(): self
    {
        return $this->end(self::STATUS_FINISHED);
    }

    public function fail(): self
    {
        return $this->end(self::STATUS_FAILED);
    }

    private function end(string $status): self
    {
        $this->endedAt = new \DateTimeImmutable();
        $this->status = $status;
        $this->progress = 100;

        return $this;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDossier(): WooDecision
    {
        return $this->dossier;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    /**
     * @return array<int, array{message: string, translation: string, placeholders: array<string, string>}>
     */
    public function getGenericErrors(): array
    {
        return $this->genericErrors;
    }

    /**
     * @return array<int, array<int, array{message: string, translation: string, placeholders: array<string, string>}>>
     */
    public function getRowErrors(): array
    {
        return $this->rowErrors;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isComparing(): bool
    {
        return $this->status === self::STATUS_COMPARING;
    }

    public function isUpdating(): bool
    {
        return $this->status === self::STATUS_UPDATING;
    }

    public function needsConfirmation(): bool
    {
        return $this->status === self::STATUS_NEEDS_CONFIRMATION;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isNotFinal(): bool
    {
        return ! $this->isFinal();
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

    /**
     * @codeCoverageIgnore
     */
    public function getFileCacheKey(): string
    {
        return 'inventory-process-run-' . $this->id->toBase58();
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function hasErrors(): bool
    {
        return count($this->genericErrors) > 0 || count($this->rowErrors) > 0;
    }

    public function hasNoErrors(): bool
    {
        return ! $this->hasErrors();
    }

    public function getTmpFilename(): ?string
    {
        return $this->tmpFilename;
    }

    public function setTmpFilename(?string $tmpFilename): self
    {
        $this->tmpFilename = $tmpFilename;

        return $this;
    }

    public function getChangeset(): ?InventoryChangeset
    {
        return $this->changeset ? new InventoryChangeset($this->changeset) : null;
    }

    public function setChangeset(InventoryChangeset $changeset): void
    {
        $this->changeset = $changeset->getAll();

        // If there is no existing ProductionReport it is an initial import, so we can skip confirmation
        $this->status = $this->dossier->getProductionReport() === null
            ? self::STATUS_CONFIRMED
            : self::STATUS_NEEDS_CONFIRMATION;
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FAILED
            || $this->status === self::STATUS_FINISHED
            || $this->status === self::STATUS_REJECTED;
    }

    public function confirm(): void
    {
        if ($this->status !== self::STATUS_NEEDS_CONFIRMATION) {
            throw new \RuntimeException('Cannot confirm production report run with status ' . $this->status);
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->progress = 0;
    }

    public function reject(): void
    {
        if ($this->status !== self::STATUS_NEEDS_CONFIRMATION && $this->status !== self::STATUS_FAILED) {
            throw new \RuntimeException('Cannot reject production report run with status ' . $this->status);
        }

        $this->status = self::STATUS_REJECTED;
    }
}
