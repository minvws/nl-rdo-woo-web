<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidMatterException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_matter';

    public function __construct(public readonly int $rowIndex)
    {
        parent::__construct(sprintf('Invalid matter in inventory row #%d', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return InvalidMatterException::MESSAGE_KEY;
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
