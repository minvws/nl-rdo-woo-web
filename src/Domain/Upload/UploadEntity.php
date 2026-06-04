<?php

declare(strict_types=1);

namespace Shared\Domain\Upload;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Doctrine\ExternalIdType;
use Shared\Doctrine\TimestampableTrait;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Service\Security\User;
use Shared\Service\Uploader\UploadGroupId;
use Shared\ValueObject\ExternalId;
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

    #[ORM\Column(type: ExternalIdType::NAME, length: 128, nullable: true, index: true)]
    private ?ExternalId $externalId = null;

    #[ORM\Column(length: 50, enumType: UploadGroupId::class)]
    protected UploadGroupId $uploadGroupId;

    #[ORM\Column(length: 50, enumType: UploadEntityStatus::class)]
    protected UploadEntityStatus $status;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $size = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimetype = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    /**
     * @var array<array-key, string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $error = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context;

    public function __construct(string $uploadId, UploadGroupId $uploadGroupId, ?User $user, InputBag $context)
    {
        $this->id = Uuid::v6();
        $this->createdAt = new CarbonImmutable();
        $this->updatedAt = new CarbonImmutable();
        $this->uploadId = $uploadId;
        $this->uploadGroupId = $uploadGroupId;
        $this->status = UploadEntityStatus::INCOMPLETE;
        $this->user = $user;
        $this->context = $context->all();
    }

    public function finishUploading(string $filename, int $size): void
    {
        if ($this->status !== UploadEntityStatus::INCOMPLETE) {
            throw UploadException::forInvalidStatusUpdate($this, UploadEntityStatus::UPLOADED);
        }

        $this->status = UploadEntityStatus::UPLOADED;
        $this->filename = $filename;
        $this->size = $size;
    }

    public function abort(): void
    {
        if ($this->status->isImmutable()) {
            throw UploadException::forInvalidStatusUpdate($this, UploadEntityStatus::ABORTED);
        }

        $this->status = UploadEntityStatus::ABORTED;
    }

    public function passValidation(string $mimeType): void
    {
        if ($this->status !== UploadEntityStatus::UPLOADED) {
            throw UploadException::forInvalidStatusUpdate($this, UploadEntityStatus::VALIDATION_PASSED);
        }

        $this->status = UploadEntityStatus::VALIDATION_PASSED;
        $this->mimetype = $mimeType;
    }

    public function failValidation(UploadValidationException $exception): void
    {
        if ($this->status !== UploadEntityStatus::UPLOADED) {
            throw UploadException::forInvalidStatusUpdate($this, UploadEntityStatus::VALIDATION_FAILED);
        }

        $this->error = [$exception->getMessage()];

        $this->status = UploadEntityStatus::VALIDATION_FAILED;
    }

    public function markAsStored(): void
    {
        if ($this->status !== UploadEntityStatus::VALIDATION_PASSED) {
            throw UploadException::forInvalidStatusUpdate($this, UploadEntityStatus::STORED);
        }

        $this->status = UploadEntityStatus::STORED;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getExternalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function setExternalId(?ExternalId $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getUploadGroupId(): UploadGroupId
    {
        return $this->uploadGroupId;
    }

    public function getStatus(): UploadEntityStatus
    {
        return $this->status;
    }

    public function getUser(): ?User
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
     * @return array<array-key, string>|null
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
