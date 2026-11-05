<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class MissingDocumentIdException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.missing_document_number';

    public function __construct(public readonly int $rowIndex)
    {
        parent::__construct(sprintf('Missing document ID in inventory row #%d', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return MissingDocumentIdException::MESSAGE_KEY;
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
