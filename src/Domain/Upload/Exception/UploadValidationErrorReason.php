<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Exception;

enum UploadValidationErrorReason: string
{
    case EXTENSION_MIME_TYPE_MISMATCH = 'extension_mime_type_mismatch';
    case FILE_SIZE_EXCEEDED = 'file_size_exceeded';
    case INVALID_EXTENSION = 'invalid_extension';
    case INVALID_MIME_TYPE = 'invalid_mime_type';
    case MIME_TYPE_NOT_DETECTED = 'mime_type_not_detected';
    case MISSING_HEADER_ROW = 'missing_header_row';
    case MULTIPLE_WORKSHEETS = 'multiple_worksheets';
    case SPREADSHEET_TOO_LARGE = 'spreadsheet_too_large';
    case UNREADABLE_SPREADSHEET = 'unreadable_spreadsheet';
    case UNSAFE_FILE = 'unsafe_file';
    case UNSUPPORTED_SPREADSHEET_EXTENSION = 'unsupported_spreadsheet_extension';
}
