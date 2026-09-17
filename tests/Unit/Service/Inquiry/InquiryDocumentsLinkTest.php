<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Shared\Service\Inquiry\InquiryDocumentsLink;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;

final class InquiryDocumentsLinkTest extends UnitTestCase
{
    public function testItExposesTheCanonicalDocumentNumber(): void
    {
        $documentNumber = DocumentNumber::fromString('prefix-matter-doc-01');
        $link = new InquiryDocumentsLink($documentNumber, ['case-01']);

        self::assertSame($documentNumber, $link->getDocumentNumber());
        self::assertSame(['case-01'], $link->getInquiryNumbers());
    }
}
