<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidLinkException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_link';

    public function __construct(
        public readonly string $link,
        public readonly int $rowIndex,
    ) {
        parent::__construct(sprintf("Invalid URL '%s' in inventory row #%d", $link, $rowIndex));
    }

    public function getMessageKey(): string
    {
        return InvalidLinkException::MESSAGE_KEY;
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
