<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject;

use App\ValueObject\FilterDetails;
use App\ValueObject\InquiryDescription;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class FilterDetailsTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $details = new FilterDetails(
            $dossierInquiries = [\Mockery::mock(InquiryDescription::class)],
            $documentInquiries = [\Mockery::mock(InquiryDescription::class)],
            $dossierNrs = ['a1', 'b2'],
        );

        self::assertEquals($dossierInquiries, $details->getDossierInquiries());
        self::assertEquals($documentInquiries, $details->getDocumentInquiries());
        self::assertEquals($dossierNrs, $details->getDossierNumbers());
    }
}
