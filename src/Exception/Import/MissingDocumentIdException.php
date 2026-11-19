<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class MissingDocumentIdException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.missing_document_number';

    public function __construct(int $rowIndex)
    {
        parent::__construct($rowIndex, 'Missing document ID');
    }

    public function getMessageKey(): string
    {
        return MissingDocumentIdException::MESSAGE_KEY;
    }
}
