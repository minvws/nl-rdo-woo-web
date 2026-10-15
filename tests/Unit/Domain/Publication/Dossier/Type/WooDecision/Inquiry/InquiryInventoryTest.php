<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

final class InquiryInventoryTest extends UnitTestCase
{
    public function testDefaultId(): void
    {
        $inquiryInventory = new InquiryInventory();

        self::assertInstanceOf(WooUuid::class, $inquiryInventory->getId());
    }

    public function testGivenId(): void
    {
        $id = Uuid::fromString($this->getFaker()->uuid());
        $inquiryInventory = new InquiryInventory($id);

        self::assertSame($id, $inquiryInventory->getId());
    }
}
