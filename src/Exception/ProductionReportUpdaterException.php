<?php

declare(strict_types=1);

namespace App\Exception;

class ProductionReportUpdaterException extends \RuntimeException
{
    public static function forStateMismatch(): self
    {
        return new self('State mismatch between database and changeset');
    }

    public static function forNoRunFound(): self
    {
        return new self('There is no run for this dossier');
    }

    public static function forExistingRunIsNotFinal(): self
    {
        return new self('Existing run is not final');
    }

    public static function forUploadCannotBeStored(): self
    {
        return new self('Could not store the production report upload');
    }
}
