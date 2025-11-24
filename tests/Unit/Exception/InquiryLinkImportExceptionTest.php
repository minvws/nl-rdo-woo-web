<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Shared\Exception\InquiryLinkImportException;

final class InquiryLinkImportExceptionTest extends TestCase
{
    public function testForMissingDocument(): void
    {
        $exception = InquiryLinkImportException::forMissingDocument('tst-123');

        self::assertEquals(
            'Document tst-123 does not exist',
            $exception->getMessage(),
        );

        self::assertEquals(
            'public.global.no_doc_number',
            $exception->getTranslationKey(),
        );

        self::assertEquals(
            [
                '{documentNr}' => 'tst-123',
            ],
            $exception->getPlaceholders(),
        );
    }
}
