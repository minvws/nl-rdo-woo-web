<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class InquiryMatterAndPublicationContextCombinationException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.inquiry.error.matter_and_publication_context_combination';

    public function __construct()
    {
        parent::__construct('A spreadsheet with a "Publicatiecontext" column cannot contain a "Matter" column');
    }

    public function getMessageKey(): string
    {
        return InquiryMatterAndPublicationContextCombinationException::MESSAGE_KEY;
    }
}
