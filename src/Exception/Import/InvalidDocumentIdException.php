<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InvalidDocumentIdException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_document_number';

    public function __construct(int $rowIndex)
    {
        parent::__construct($rowIndex, 'Invalid document ID');
    }

    public function getMessageKey(): string
    {
        return InvalidDocumentIdException::MESSAGE_KEY;
    }
}
