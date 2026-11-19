<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InvalidThreadIdException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_thread_id';

    public function __construct(int $rowIndex)
    {
        parent::__construct($rowIndex, 'Invalid thread ID');
    }

    public function getMessageKey(): string
    {
        return InvalidThreadIdException::MESSAGE_KEY;
    }
}
