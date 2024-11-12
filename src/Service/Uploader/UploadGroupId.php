<?php

declare(strict_types=1);

namespace App\Service\Uploader;

use App\Domain\Upload\FileType\FileType;

enum UploadGroupId: string
{
    case MAIN_DOCUMENTS = 'main-documents';
    case ATTACHMENTS = 'attachments';

    /**
     * @return list<FileType>
     */
    public function getFileTypes(): array
    {
        return [FileType::PDF, FileType::XLS, FileType::DOC, FileType::PPT, FileType::TXT];
    }
}
