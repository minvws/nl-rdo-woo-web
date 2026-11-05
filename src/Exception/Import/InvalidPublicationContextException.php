<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidPublicationContextException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_publication_context';

    public function __construct(public readonly int $rowIndex)
    {
        parent::__construct(sprintf('Invalid publication context in inventory row #%d', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return InvalidPublicationContextException::MESSAGE_KEY;
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
