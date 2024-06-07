<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

class MainDocumentAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('This dossier already has a main document');
    }
}
