<?php

declare(strict_types=1);

namespace App\Service\Uploader;

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
}
