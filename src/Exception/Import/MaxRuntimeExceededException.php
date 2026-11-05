<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class MaxRuntimeExceededException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.maximum_processing_time_exceeded';

    public function __construct()
    {
        parent::__construct('The maximum processing time has been exceeded');
    }

    public function getMessageKey(): string
    {
        return MaxRuntimeExceededException::MESSAGE_KEY;
    }
}
