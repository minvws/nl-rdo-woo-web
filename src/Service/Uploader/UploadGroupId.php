<?php

declare(strict_types=1);

namespace App\Service\Uploader;

use App\Domain\Upload\FileType\FileType;

enum UploadGroupId: string
{
    case MAIN_DOCUMENTS = 'main-documents';
    case ATTACHMENTS = 'attachments';
    case WOO_DECISION_DOCUMENTS = 'woo-decision-documents';

    /**
     * @return list<FileType>
     */
    public function getFileTypes(): array
    {
        return match ($this) {
            self::WOO_DECISION_DOCUMENTS => [FileType::PDF, FileType::XLS, FileType::DOC, FileType::PPT, FileType::TXT, FileType::ZIP],
            default => [FileType::PDF, FileType::XLS, FileType::DOC, FileType::PPT, FileType::TXT],
        };
    }

    /**
     * @return list<string>
     */
    public function getExtensions(): array
    {
        $exts = [];
        foreach ($this->getFileTypes() as $fileType) {
            $exts = array_merge($exts, $fileType->getExtensions());
        }

        return array_values(array_unique($exts));
    }

    /**
     * @return list<string>
     */
    public function getMimeTypes(): array
    {
        $mimeTypes = [];
        foreach ($this->getFileTypes() as $fileType) {
            $mimeTypes = array_merge($mimeTypes, $fileType->getMimeTypes());
        }

        return array_values(array_unique($mimeTypes));
    }

    /**
     * @return list<string>
     */
    public function getFileTypeNames(): array
    {
        $names = [];
        foreach ($this->getFileTypes() as $fileType) {
            $names[] = $fileType->getTypeName();
        }

        return $names;
    }
}
