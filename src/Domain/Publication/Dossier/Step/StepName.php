<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Step;

enum StepName: string
{
    case DETAILS = 'details';
    case DECISION = 'decision';
    case DOCUMENTS = 'documents';
    case PUBLICATION = 'publication';
    case CONTENT = 'content';
}
