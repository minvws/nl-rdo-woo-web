<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function implode;
use function strval;

final class InquiryInvalidInquiryNumbersException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.inquiry.error.inquiry_numbers_invalid';

    /**
     * @param array<array-key, string> $inquiryNumbers
     */
    public function __construct(
        public readonly int $rowNumber,
        public readonly array $inquiryNumbers,
    ) {
        parent::__construct('Invalid inquiry numbers');
    }

    public function getMessageKey(): string
    {
        return InquiryInvalidInquiryNumbersException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{rownumber}' => strval($this->rowNumber),
            '{inquiryNumbers}' => implode(', ', $this->inquiryNumbers),
        ];
    }
}
