<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Shared\Doctrine\TimestampableTrait;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class InquiryInventory implements EntityWithFileInfo
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\OneToOne(inversedBy: 'inventory', targetEntity: Inquiry::class)]
    #[ORM\JoinColumn(name: 'inquiry_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private Inquiry $inquiry;

    #[Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    private FileInfo $file;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->file = new FileInfo();
    }

    public function setInquiry(Inquiry $inquiry): self
    {
        $this->inquiry = $inquiry;

        return $this;
    }

    public function getInquiry(): Inquiry
    {
        return $this->inquiry;
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
        return 'inquiry-inventory-' . $this->id->toBase58();
    }
}
