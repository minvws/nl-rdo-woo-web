<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\SpreadsheetTable;

use RuntimeException;
use Shared\Domain\Upload\Exception\UploadValidationErrorReason;

final class SpreadsheetTableException extends RuntimeException
{
    private function __construct(
        string $message,
        private readonly UploadValidationErrorReason $errorReason,
    ) {
        parent::__construct($message);
    }

    public static function forMultipleWorksheets(): self
    {
        return new self('spreadsheet contains multiple worksheets', UploadValidationErrorReason::MULTIPLE_WORKSHEETS);
    }

    public static function forMissingHeaderRow(): self
    {
        return new self('first worksheet does not contain a header row', UploadValidationErrorReason::MISSING_HEADER_ROW);
    }

    public static function forUnsupportedExtension(): self
    {
        return new self('unsupported spreadsheet extension', UploadValidationErrorReason::UNSUPPORTED_SPREADSHEET_EXTENSION);
    }

    public static function forUnreadableFile(): self
    {
        return new self('spreadsheet could not be read', UploadValidationErrorReason::UNREADABLE_SPREADSHEET);
    }

    public function getErrorReason(): UploadValidationErrorReason
    {
        return $this->errorReason;
    }
}
