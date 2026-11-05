<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class ProcessingFailedException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.processing_inventory';

    public function __construct()
    {
        parent::__construct('Production report processing failed');
    }

    public function getMessageKey(): string
    {
        return ProcessingFailedException::MESSAGE_KEY;
    }
}
