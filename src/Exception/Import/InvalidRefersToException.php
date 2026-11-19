<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class InvalidRefersToException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_refers_to';

    public function __construct(
        int $rowIndex,
        public readonly string $refersTo,
    ) {
        parent::__construct($rowIndex, sprintf("Invalid refers to value '%s'", $refersTo));
    }

    public function getMessageKey(): string
    {
        return InvalidRefersToException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{refersTo}' => $this->refersTo,
        ];
    }
}
