<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use Exception;

use function sprintf;
use function strval;

final class RowProcessingException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.processing_inventory_row';

    public function __construct(public readonly int $rowIndex, Exception $exception)
    {
        parent::__construct(sprintf('Error while processing row %d in the spreadsheet', $rowIndex), previous: $exception);
    }

    public function getMessageKey(): string
    {
        return RowProcessingException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{rowIndex}' => strval($this->rowIndex),
        ];
    }
}
