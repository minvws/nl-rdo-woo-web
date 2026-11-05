<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidRefersToException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_refers_to';

    public function __construct(
        public readonly string $refersTo,
        public readonly int $rowIndex,
    ) {
        parent::__construct(sprintf("Invalid refers to value '%s' in inventory row #%d", $refersTo, $rowIndex));
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
            '{rownumber}' => strval($this->rowIndex),
            '{refersTo}' => $this->refersTo,
        ];
    }
}
