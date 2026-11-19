<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function strval;

final class InvalidDocumentIdLengthException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_document_id_length';

    public function __construct(
        int $rowIndex,
        public readonly int $min,
        public readonly int $max,
    ) {
        parent::__construct($rowIndex, 'Invalid document ID length');
    }

    public function getMessageKey(): string
    {
        return InvalidDocumentIdLengthException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{min}' => strval($this->min),
            '{max}' => strval($this->max),
        ];
    }
}
