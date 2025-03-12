<?php

declare(strict_types=1);

namespace App\Domain\Publication;

use App\Doctrine\FileCacheKeyBasedOnClassAndIdTrait;
use App\Doctrine\TimestampableTrait;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Service\Uploader\UploadGroupId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This trait contains shared properties and methods for main document entities and attachment entities.
 * They are mostly identical so to prevent duplications these are placed in a trait, to avoid inheritance between
 * maindocuments and attachments.
 */
trait AttachmentAndMainDocumentEntityTrait
{
    use TimestampableTrait;
    use FileCacheKeyBasedOnClassAndIdTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    protected Uuid $id;

    #[ORM\Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    #[Assert\Valid()]
    protected FileInfo $fileInfo;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\LessThanOrEqual(value: 'now')]
    protected \DateTimeImmutable $formalDate;

    #[ORM\Column(length: 255, enumType: AttachmentType::class)]
    #[Assert\Choice(callback: 'getAllowedTypes')]
    protected AttachmentType $type;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255)]
    protected string $internalReference = '';

    #[ORM\Column(length: 255, enumType: AttachmentLanguage::class)]
    protected AttachmentLanguage $language;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    protected array $grounds = [];

    abstract public static function getUploadGroupId(): UploadGroupId;

    abstract public function getDossier(): AbstractDossier;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->fileInfo = new FileInfo();
    }

    public function getFormalDate(): \DateTimeImmutable
    {
        return $this->formalDate;
    }

    public function setFormalDate(\DateTimeImmutable $formalDate): void
    {
        $this->formalDate = $formalDate;
    }

    public function getType(): AttachmentType
    {
        return $this->type;
    }

    public function setType(AttachmentType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return AttachmentType[]
     */
    public static function getAllowedTypes(): array
    {
        return AttachmentType::getCasesWithout(AttachmentType::OTHER);
    }

    public function getInternalReference(): string
    {
        return $this->internalReference;
    }

    public function setInternalReference(string $internalReference): void
    {
        $this->internalReference = $internalReference;
    }

    public function getLanguage(): AttachmentLanguage
    {
        return $this->language;
    }

    public function setLanguage(AttachmentLanguage $language): void
    {
        $this->language = $language;
    }

    /**
     * @return list<string>
     */
    public function getGrounds(): array
    {
        return $this->grounds;
    }

    /**
     * @param array<array-key,string> $grounds
     *
     * @return $this
     */
    public function setGrounds(array $grounds): static
    {
        $this->grounds = array_values($grounds);

        return $this;
    }

    public function getId(): Uuid
    {
        return $this->id;
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
