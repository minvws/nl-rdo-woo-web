<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class LinkTooLongException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.link_too_long';

    public function __construct(
        public readonly string $link,
        public readonly int $rowIndex,
    ) {
        parent::__construct(sprintf('Link is too long in inventory row #%d', $rowIndex));
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
            '{rownumber}' => strval($this->rowIndex),
        ];
    }
}
