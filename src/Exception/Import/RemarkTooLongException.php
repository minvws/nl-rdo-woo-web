<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function strval;

final class RemarkTooLongException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.remark_too_long';

    public function __construct(
        int $rowIndex,
        public readonly int $max,
    ) {
        parent::__construct($rowIndex, 'Remark is too long');
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
            '{max}' => strval($this->max),
        ];
    }
}
