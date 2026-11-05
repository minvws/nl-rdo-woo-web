<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class MissingReferredDocumentException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.referred_document_does_not_exist';

    public function __construct(public readonly string $documentNumber)
    {
        parent::__construct(sprintf('The referred document %s does not exist', $documentNumber));
    }

    public function getMessageKey(): string
    {
        return MissingReferredDocumentException::MESSAGE_KEY;
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
