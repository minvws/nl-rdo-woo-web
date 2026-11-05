<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class DuplicateDocumentNumberException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.document_not_unique';

    public function __construct(public readonly string $documentNumber)
    {
        parent::__construct(sprintf('The document number %s is not unique within the inventory', $documentNumber));
    }

    public function getMessageKey(): string
    {
        return DuplicateDocumentNumberException::MESSAGE_KEY;
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
