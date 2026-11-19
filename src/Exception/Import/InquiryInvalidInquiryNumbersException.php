<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function implode;

final class InquiryInvalidInquiryNumbersException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.inquiry.error.inquiry_numbers_invalid';

    /**
     * @param array<array-key, string> $inquiryNumbers
     */
    public function __construct(
        int $rowIndex,
        public readonly array $inquiryNumbers,
    ) {
        parent::__construct($rowIndex, 'Invalid inquiry numbers');
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
            '{inquiryNumbers}' => implode(', ', $this->inquiryNumbers),
        ];
    }
}
