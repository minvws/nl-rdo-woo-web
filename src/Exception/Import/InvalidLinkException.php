<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class InvalidLinkException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_link';

    public function __construct(
        int $rowIndex,
        public readonly string $link,
    ) {
        parent::__construct($rowIndex, sprintf("Invalid URL '%s'", $link));
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
            '{link}' => $this->link,
        ];
    }
}
