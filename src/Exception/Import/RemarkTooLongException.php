<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class RemarkTooLongException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.remark_too_long';

    public function __construct(
        public readonly int $rowIndex,
        public readonly int $max,
    ) {
        parent::__construct(sprintf('Remark in row #%d is too long', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return RemarkTooLongException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{rownumber}' => strval($this->rowIndex),
            '{max}' => strval($this->max),
        ];
    }
}
