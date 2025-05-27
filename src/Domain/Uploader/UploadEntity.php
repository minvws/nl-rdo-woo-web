<?php

declare(strict_types=1);

namespace App\Domain\Uploader;

use App\Doctrine\TimestampableTrait;
use App\Domain\Uploader\Exception\UploadException;
use App\Domain\Uploader\Exception\UploadValidationException;
use App\Entity\User;
use App\Service\Uploader\UploadGroupId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UploadEntityRepository::class)]
#[ORM\Table(name: 'upload')]
#[ORM\HasLifecycleCallbacks]
class UploadEntity
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\Column(length: 50)]
    private string $uploadId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 50, enumType: UploadGroupId::class)]
    protected UploadGroupId $uploadGroupId;

    #[ORM\Column(length: 50, enumType: UploadStatus::class)]
    protected UploadStatus $status;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $size = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimetype = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $error = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context;

    public function __construct(string $uploadId, UploadGroupId $uploadGroupId, User $user, InputBag $context)
    {
        $this->id = Uuid::v6();
        $this->uploadId = $uploadId;
        $this->uploadGroupId = $uploadGroupId;
        $this->status = UploadStatus::INCOMPLETE;
        $this->user = $user;
        $this->context = $context->all();
    }

    public function finishUploading(string $filename, int $size): void
    {
        if ($this->status !== UploadStatus::INCOMPLETE) {
            throw UploadException::forInvalidStatusUpdate($this, UploadStatus::UPLOADED);
        }

        $this->status = UploadStatus::UPLOADED;
        $this->filename = $filename;
        $this->size = $size;
    }

    public function abort(): void
    {
        if ($this->status->isImmutable()) {
            throw UploadException::forInvalidStatusUpdate($this, UploadStatus::ABORTED);
        }

        $this->status = UploadStatus::ABORTED;
    }

    public function passValidation(string $mimeType): void
    {
        if ($this->status !== UploadStatus::UPLOADED) {
            throw UploadException::forInvalidStatusUpdate($this, UploadStatus::VALIDATION_PASSED);
        }

        $this->status = UploadStatus::VALIDATION_PASSED;
        $this->mimetype = $mimeType;
    }

    public function failValidation(UploadValidationException $exception): void
    {
        if ($this->status !== UploadStatus::UPLOADED) {
            throw UploadException::forInvalidStatusUpdate($this, UploadStatus::VALIDATION_FAILED);
        }

        $this->error = [$exception->getMessage()];

        $this->status = UploadStatus::VALIDATION_FAILED;
    }

    public function markAsStored(): void
    {
        if ($this->status !== UploadStatus::VALIDATION_PASSED) {
            throw UploadException::forInvalidStatusUpdate($this, UploadStatus::STORED);
        }

        $this->status = UploadStatus::STORED;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getUploadGroupId(): UploadGroupId
    {
        return $this->uploadGroupId;
    }

    public function getStatus(): UploadStatus
    {
        return $this->status;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @return string[]|null
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    public function getContext(): InputBag
    {
        return new InputBag($this->context ?? []);
    }
}
