<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use Exception;

use function sprintf;

final class InquiryUnexpectedProcessingException extends ImportException
{
    public const string MESSAGE_KEY = 'publication.inquiry.error.processing_failed';

    public function __construct(Exception $exception)
    {
        parent::__construct(sprintf('Uncaught exception during inquiry link import: %s', $exception->getMessage()), previous: $exception);
    }

    public function getMessageKey(): string
    {
        return InquiryUnexpectedProcessingException::MESSAGE_KEY;
    }
}
