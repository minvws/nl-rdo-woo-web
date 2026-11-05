<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function implode;

final class MissingHeadersException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.missing_inventory_header';

    /**
     * @param array<array-key, string> $missingHeaders
     */
    public function __construct(public readonly array $missingHeaders)
    {
        parent::__construct('Could not find the correct headers in the spreadsheet');
    }

    public function getMessageKey(): string
    {
        return MissingHeadersException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{headername}' => implode(', ', $this->missingHeaders),
        ];
    }
}
