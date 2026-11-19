<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InvalidFamilyIdException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_family_id';

    public function __construct(int $rowIndex)
    {
        parent::__construct($rowIndex, 'Invalid family ID');
    }

    public function getMessageKey(): string
    {
        return InvalidFamilyIdException::MESSAGE_KEY;
    }
}
