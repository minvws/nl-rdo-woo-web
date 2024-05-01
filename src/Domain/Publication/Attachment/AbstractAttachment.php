<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Entity\PublicationItem;
use App\Service\Uploader\UploadGroupId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractAttachment extends PublicationItem
{
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\LessThanOrEqual(value: 'now')]
    protected \DateTimeImmutable $formalDate;

    #[ORM\Column(length: 255, enumType: AttachmentType::class)]
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

    abstract public function getUploadGroupId(): UploadGroupId;

    abstract public function getDossier(): AbstractDossier;

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
}
