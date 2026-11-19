<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InvalidMatterException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_matter';

    public function __construct(int $rowIndex)
    {
        parent::__construct($rowIndex, 'Invalid matter');
    }

    public function getMessageKey(): string
    {
        return InvalidMatterException::MESSAGE_KEY;
    }
}
