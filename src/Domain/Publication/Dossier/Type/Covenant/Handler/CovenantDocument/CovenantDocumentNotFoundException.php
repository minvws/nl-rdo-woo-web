<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

class CovenantDocumentNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('This covenant has no document');
    }
}
