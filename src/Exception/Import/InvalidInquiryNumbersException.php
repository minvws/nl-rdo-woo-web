<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;
use function strval;

final class InvalidInquiryNumbersException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.inquiry_numbers_invalid';

    public function __construct(
        public readonly string $inquiryNumbers,
        public readonly int $rowIndex,
    ) {
        parent::__construct(sprintf("Inquiry numbers '%s' are not valid in inventory row #%d", $inquiryNumbers, $rowIndex));
    }

    public function getMessageKey(): string
    {
        return InvalidInquiryNumbersException::MESSAGE_KEY;
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
