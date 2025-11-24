<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

class MainDocumentNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('This dossier has no main document');
    }
}
