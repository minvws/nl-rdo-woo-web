<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use function sprintf;

final class InvalidInquiryNumbersException extends RowImportException
{
    public const string MESSAGE_KEY = 'publication.dossier.error.inquiry_numbers_invalid';

    public function __construct(
        int $rowIndex,
        public readonly string $inquiryNumbers,
    ) {
        parent::__construct($rowIndex, sprintf("Invalid inquiry numbers '%s'", $inquiryNumbers));
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
            '{inquiryNumbers}' => $this->inquiryNumbers,
        ];
    }
}
