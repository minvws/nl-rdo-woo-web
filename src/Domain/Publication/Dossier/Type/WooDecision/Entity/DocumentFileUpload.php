<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Doctrine\TimestampableTrait;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUploadError;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUploadStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileUploadRepository;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\FileInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DocumentFileUploadRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DocumentFileUpload implements EntityWithFileInfo
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: DocumentFileSet::class)]
    #[ORM\JoinColumn(name: 'document_file_set_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private DocumentFileSet $documentFileSet;

    #[ORM\Embedded(class: FileInfo::class, columnPrefix: 'file_')]
    protected FileInfo $fileInfo;

    #[ORM\Column(length: 255, nullable: false, enumType: DocumentFileUploadStatus::class)]
    private DocumentFileUploadStatus $status;

    #[ORM\Column(length: 255, nullable: true, enumType: DocumentFileUploadError::class)]
    private ?DocumentFileUploadError $error = null;

    public function __construct(DocumentFileSet $documentFileSet)
    {
        $this->id = Uuid::v6();
        $this->documentFileSet = $documentFileSet;
        $this->status = DocumentFileUploadStatus::PENDING;
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

    public function getStatus(): DocumentFileUploadStatus
    {
        return $this->status;
    }

    public function setStatus(DocumentFileUploadStatus $status): void
    {
        $this->status = $status;
    }

    public function getError(): ?DocumentFileUploadError
    {
        return $this->error;
    }

    public function setError(DocumentFileUploadError $error): void
    {
        $this->error = $error;
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
