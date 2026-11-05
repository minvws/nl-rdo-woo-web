<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class MissingDocumentException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.missing_document';

    public function __construct(public readonly string $documentNumber)
    {
        parent::__construct(sprintf('Missing document %s in the inventory', $documentNumber));
    }

    public function getMessageKey(): string
    {
        return MissingDocumentException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{documentNumber}' => $this->documentNumber,
        ];
    }
}
