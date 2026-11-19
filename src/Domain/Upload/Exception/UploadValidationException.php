<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Exception;

use RuntimeException;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableException;
use Shared\Domain\Upload\FileType\FileType;
use Shared\Domain\Upload\UploadEntity;
use Throwable;

use function sprintf;

class UploadValidationException extends RuntimeException
{
    private function __construct(
        string $message,
        private readonly UploadValidationErrorReason $errorReason,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function forInvalidMimetype(UploadEntity $uploadEntity, string $mimetype): self
    {
        return new self(sprintf(
            'Mimetype %s not allowed for upload group %s',
            $mimetype,
            $uploadEntity->getUploadGroupId()->value,
        ), UploadValidationErrorReason::INVALID_MIME_TYPE);
    }

    public static function forInvalidExtension(UploadEntity $uploadEntity, string $extension): self
    {
        return new self(sprintf(
            'Extension %s not allowed for upload group %s',
            $extension,
            $uploadEntity->getUploadGroupId()->value,
        ), UploadValidationErrorReason::INVALID_EXTENSION);
    }

    public static function forMismatchBetweenExtensionAndMimetype(?FileType $fileType, string $mimetype): self
    {
        return new self(sprintf(
            'Expected a file of type %s, but got a file of mime-type %s',
            $fileType?->getTypeName() ?? 'unknown',
            $mimetype,
        ), UploadValidationErrorReason::EXTENSION_MIME_TYPE_MISMATCH);
    }

    public static function forUnsafeFile(): self
    {
        return new self('File contains unsafe content', UploadValidationErrorReason::UNSAFE_FILE);
    }

    public static function forCannotDetectMimetype(UploadEntity $uploadEntity): self
    {
        return new self(sprintf(
            'Mimetype cannot be determined for UploadEntity %s',
            $uploadEntity->getId()->toRfc4122(),
        ), UploadValidationErrorReason::MIME_TYPE_NOT_DETECTED);
    }

    public static function forInvalidSpreadsheet(SpreadsheetTableException $spreadsheetTableException): self
    {
        return new self(
            $spreadsheetTableException->getMessage(),
            $spreadsheetTableException->getErrorReason(),
            $spreadsheetTableException,
        );
    }

    public static function forSpreadsheetTooLarge(): self
    {
        return new self('Spreadsheet size exceeds the allowed limit', UploadValidationErrorReason::SPREADSHEET_TOO_LARGE);
    }

    public static function forFilesizeExceeded(UploadEntity $uploadEntity, FileType $fileType): self
    {
        return new self(sprintf(
            'File size exceeds the allowed limit set for %s files (%d bytes). UploadEntity id: %s',
            $fileType->getTypeName(),
            $fileType->getMaxUploadSize(),
            $uploadEntity->getId()->toRfc4122(),
        ), UploadValidationErrorReason::FILE_SIZE_EXCEEDED);
    }

    public function getErrorReason(): UploadValidationErrorReason
    {
        return $this->errorReason;
    }
}
