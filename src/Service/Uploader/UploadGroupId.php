<?php

declare(strict_types=1);

namespace App\Service\Uploader;

enum UploadGroupId: string
{
    case DEFAULT = 'default';
    case WOO_DECISION_ATTACHMENTS = 'woo-decision-attachments';
    case COVENANT_DOCUMENTS = 'covenant-documents';
    case COVENANT_ATTACHMENTS = 'covenant-attachments';
}
