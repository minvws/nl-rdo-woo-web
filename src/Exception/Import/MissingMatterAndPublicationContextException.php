<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class MissingMatterAndPublicationContextException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.matter_or_publication_context_required';

    public function __construct(int $rowIndex)
    {
        parent::__construct($rowIndex, 'Missing matter and publication context');
    }

    public function getMessageKey(): string
    {
        return MissingMatterAndPublicationContextException::MESSAGE_KEY;
    }
}
