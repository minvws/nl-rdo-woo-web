<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception\Import;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shared\Exception\Import\InquiryUnexpectedProcessingException;

class InquiryUnexpectedProcessingExceptionTest extends TestCase
{
    public function testGetMessage(): void
    {
        $exception = new InquiryUnexpectedProcessingException(new RuntimeException('foo'));

        self::assertSame('Uncaught exception during inquiry link import: foo', $exception->getMessage());
    }

    public function testGetPrevious(): void
    {
        $previous = new RuntimeException('foo');
        $exception = new InquiryUnexpectedProcessingException($previous);

        self::assertSame($previous, $exception->getPrevious());
    }

    public function testGetMessageKey(): void
    {
        $exception = new InquiryUnexpectedProcessingException(new RuntimeException('foo'));

        self::assertSame('publication.inquiry.error.processing_failed', $exception->getMessageKey());
    }
}
