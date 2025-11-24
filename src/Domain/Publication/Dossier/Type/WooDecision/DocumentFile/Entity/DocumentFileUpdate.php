<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shared\Doctrine\TimestampableTrait;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'unique_document_for_set', columns: ['document_file_set_id', 'document_id'])]
class DocumentFileUpdate implements EntityWithFileInfo
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\Column(length: 255, nullable: false, enumType: DocumentFileUpdateType::class)]
    private DocumentFileUpdateType $type;

    #[ORM\Column(length: 255, nullable: false, enumType: DocumentFileUpdateStatus::class)]
    private DocumentFileUpdateStatus $status;

    #[ORM\Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    protected FileInfo $fileInfo;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: DocumentFileSet::class, inversedBy: 'updates')]
        #[ORM\JoinColumn(name: 'document_file_set_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
        private DocumentFileSet $documentFileSet,
        #[ORM\ManyToOne(targetEntity: Document::class)]
        #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
        private Document $document,
    ) {
        $this->id = Uuid::v6();
        $this->type = DocumentFileUpdateType::forDocument($this->document);
        $this->status = DocumentFileUpdateStatus::PENDING;
        $this->fileInfo = new FileInfo();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDocumentFileSet(): DocumentFileSet
    {
        return $this->documentFileSet;
    }

    public function getStatus(): DocumentFileUpdateStatus
    {
        return $this->status;
    }

    public function setStatus(DocumentFileUpdateStatus $status): void
    {
        $this->status = $status;
    }

    public function getType(): DocumentFileUpdateType
    {
        return $this->type;
    }

    public function getDocument(): Document
    {
        return $this->document;
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

    public function getFileCacheKey(): string
    {
        return $this->id->toRfc4122();
    }
}
