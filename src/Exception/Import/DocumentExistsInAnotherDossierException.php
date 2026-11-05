<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class DocumentExistsInAnotherDossierException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.document_already_exists';

    public function __construct(public readonly string $documentId)
    {
        parent::__construct(sprintf('Document %s already exists in another dossier', $documentId));
    }

    public function getMessageKey(): string
    {
        return DocumentExistsInAnotherDossierException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{document_id}' => $this->documentId,
        ];
    }
}
