<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow;

enum StepName: string
{
    case DETAILS = 'details';
    case DECISION = 'decision';
    case DOCUMENTS = 'documents';
    case PUBLICATION = 'publication';
}
