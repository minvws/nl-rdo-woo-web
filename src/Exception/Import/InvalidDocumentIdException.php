<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidDocumentIdException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_document_number';

    public function __construct(public readonly int $rowIndex)
    {
        parent::__construct(sprintf('Invalid document ID in inventory row #%d', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return InvalidDocumentIdException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{rownumber}' => strval($this->rowIndex),
        ];
    }
}
