<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use Exception;

use function sprintf;

final class UnexpectedProcessingException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.processing_inventory';

    public function __construct(Exception $exception)
    {
        parent::__construct(sprintf('Uncaught exception during inventory processing: %s', $exception->getMessage()), previous: $exception);
    }

    public function getMessageKey(): string
    {
        return UnexpectedProcessingException::MESSAGE_KEY;
    }
}
