<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

class CovenantDocumentAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('This covenant already has a document');
    }
}
