<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\FileProvider;

enum DossierFileType: string
{
    case DOCUMENT = 'document';
    case ATTACHMENT = 'attachment';
    case MAIN_DOCUMENT = 'main_document';
    case INVENTORY = 'inventory';
    case PRODUCTION_REPORT = 'production_report';
}
