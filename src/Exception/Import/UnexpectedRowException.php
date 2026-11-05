<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use Exception;

final class UnexpectedRowException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.generic_document_row_exception';

    public function __construct(Exception $exception)
    {
        parent::__construct($exception->getMessage(), previous: $exception);
    }

    public function getMessageKey(): string
    {
        return UnexpectedRowException::MESSAGE_KEY;
    }
}
