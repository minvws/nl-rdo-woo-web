<?php

declare(strict_types=1);

namespace App\Service\Uploader;

use App\Domain\Upload\FileType\FileType;

enum UploadGroupId: string
{
    case DEFAULT = 'default';
    case WOO_DECISION_ATTACHMENTS = 'woo-decision-attachments';
    case COVENANT_DOCUMENTS = 'covenant-documents';
    case COVENANT_ATTACHMENTS = 'covenant-attachments';
    case ANNUAL_REPORT_DOCUMENTS = 'annual-report-documents';
    case ANNUAL_REPORT_ATTACHMENTS = 'annual-report-attachments';
    case INVESTIGATION_REPORT_DOCUMENTS = 'investigation-report-documents';
    case INVESTIGATION_REPORT_ATTACHMENTS = 'investigation-report-attachments';
    case DISPOSITION_DOCUMENTS = 'disposition-documents';
    case DISPOSITION_ATTACHMENTS = 'disposition-attachments';
    case COMPLAINT_JUDGEMENT_DOCUMENTS = 'complaint-judgement-documents';

    /**
     * @return FileType[]
     */
    public function getFileTypes(): array
    {
        return match ($this) {
            self::DEFAULT => FileType::cases(),
            default => [FileType::PDF, FileType::XLS, FileType::DOC, FileType::PPT, FileType::TXT],
        };
    }
}
