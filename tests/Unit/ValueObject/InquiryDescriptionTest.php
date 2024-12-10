<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\ValueObject\InquiryDescription;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class InquiryDescriptionTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $description = new InquiryDescription(
            $id = 'foo-123',
            $caseNr = 'bar-456',
        );

        self::assertEquals($id, $description->getId());
        self::assertEquals($caseNr, $description->getCasenumber());
    }

    public function testFromEntity(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->expects('getId->toRfc4122')->andReturn($id = 'foo-123');
        $inquiry->expects('getCasenr')->andReturn($caseNr = 'bar-456');

        $description = InquiryDescription::fromEntity($inquiry);

        self::assertEquals($id, $description->getId());
        self::assertEquals($caseNr, $description->getCasenumber());
    }
}
