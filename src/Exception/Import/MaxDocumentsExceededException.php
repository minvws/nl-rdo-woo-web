<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function strval;

final class MaxDocumentsExceededException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.max_documents_exceeded';

    public function __construct(public readonly int $max)
    {
        parent::__construct('The maximum number of documents per dossier has been exceeded');
    }

    public function getMessageKey(): string
    {
        return MaxDocumentsExceededException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{max}' => strval($this->max),
        ];
    }
}
