<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function strval;

final class LinkTooLongException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.link_too_long';

    public function __construct(
        int $rowIndex,
        public readonly string $link,
        public readonly int $max,
    ) {
        parent::__construct($rowIndex, 'Link is too long');
    }

    public function getMessageKey(): string
    {
        return LinkTooLongException::MESSAGE_KEY;
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
