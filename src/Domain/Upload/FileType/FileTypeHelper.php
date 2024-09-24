<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

use App\Service\Uploader\UploadGroupId;
use Symfony\Component\Mime\MimeTypesInterface;

class FileTypeHelper
{
    /**
     * @var array<string,FileType>
     */
    private ?array $mapping = null;

    public function __construct(
        private readonly MimeTypesInterface $mimeTypes,
    ) {
    }

    public function getFileType(string $mimeType): ?FileType
    {
        if ($this->mapping === null) {
            $this->createMapping();
        }

        return $this->mapping[$mimeType] ?? null;
    }

    /**
     * @return string[]
     */
    public function getMimeTypes(FileType ...$fileTypes): array
    {
        $allowedExtensions = FileType::getExtensionsForTypes(...$fileTypes);

        $mimeTypes = [];
        foreach ($allowedExtensions as $extension) {
            $mimeTypes = array_merge($mimeTypes, $this->mimeTypes->getMimeTypes($extension));
        }

        return $mimeTypes;
    }

    /**
     * @return string[]
     */
    public function getMimeTypesByUploadGroup(UploadGroupId $uploadGroupId): array
    {
        return $this->getMimeTypes(...$uploadGroupId->getFileTypes());
    }

    /**
     * @return string[]
     */
    public function getExtensionsByUploadGroup(UploadGroupId $uploadGroupId): array
    {
        $extensions = [];
        foreach ($uploadGroupId->getFileTypes() as $fileType) {
            $extensions = array_merge($extensions, FileType::getExtensionsForTypes($fileType));
        }

        return $extensions;
    }

    /**
     * @return string[]
     */
    public function getTypeNamesByUploadGroup(UploadGroupId $uploadGroupId): array
    {
        return FileType::getTypeNamesForTypes(...$uploadGroupId->getFileTypes());
    }

    private function createMapping(): void
    {
        $this->mapping = [];

        foreach (FileType::cases() as $fileType) {
            foreach (FileType::getExtensionsForTypes($fileType) as $extension) {
                foreach ($this->mimeTypes->getMimeTypes($extension) as $mimeType) {
                    $this->mapping[$mimeType] = $fileType;
                }
            }
        }
    }
}
