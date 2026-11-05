<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class MatterAndPublicationContextCombinationException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.matter_and_publication_context_combination';

    public function __construct()
    {
        parent::__construct('A production report with a "Publicatiecontext" column cannot contain a "Matter" column');
    }

    public function getMessageKey(): string
    {
        return MatterAndPublicationContextCombinationException::MESSAGE_KEY;
    }
}
