<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InvalidPublicationContextException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_publication_context';

    public function __construct(int $rowIndex)
    {
        parent::__construct($rowIndex, 'Invalid publication context');
    }

    public function getMessageKey(): string
    {
        return InvalidPublicationContextException::MESSAGE_KEY;
    }
}
