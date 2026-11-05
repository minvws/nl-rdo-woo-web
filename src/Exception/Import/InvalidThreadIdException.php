<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidThreadIdException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_thread_id';

    public function __construct(public readonly int $rowIndex)
    {
        parent::__construct(sprintf('ThreadId in row #%d is invalid', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return InvalidThreadIdException::MESSAGE_KEY;
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
