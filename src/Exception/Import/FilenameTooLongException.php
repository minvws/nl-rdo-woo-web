<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function strval;

final class FilenameTooLongException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.file_name_too_long';

    public function __construct(
        int $rowIndex,
        public readonly string $filename,
        public readonly int $max,
    ) {
        parent::__construct($rowIndex, 'Filename is too long');
    }

    public function getMessageKey(): string
    {
        return FilenameTooLongException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{filename}' => $this->filename,
            '{max}' => strval($this->max),
        ];
    }
}
