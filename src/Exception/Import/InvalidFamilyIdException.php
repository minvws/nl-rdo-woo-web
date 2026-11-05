<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidFamilyIdException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.invalid_family_id';

    public function __construct(public readonly int $rowIndex)
    {
        parent::__construct(sprintf('FamilyId in row #%d is invalid', $rowIndex));
    }

    public function getMessageKey(): string
    {
        return InvalidFamilyIdException::MESSAGE_KEY;
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
