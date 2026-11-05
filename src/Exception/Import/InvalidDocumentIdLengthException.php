<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidDocumentIdLengthException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_document_id_length';

    public function __construct(
        public readonly int $rowIndex,
        public readonly int $min,
        public readonly int $max,
    ) {
        parent::__construct(sprintf('Invalid document ID length in inventory row #%d', $rowIndex));
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
            '{rownumber}' => strval($this->rowIndex),
            '{min}' => strval($this->min),
            '{max}' => strval($this->max),
        ];
    }
}
