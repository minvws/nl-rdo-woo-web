<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\AntiVirus;

use App\Domain\Upload\AntiVirus\ClamAvClientFactory;
use App\Tests\Unit\UnitTestCase;
use Socket\Raw\Exception;

final class ClamAvClientFactoryTest extends UnitTestCase
{
    public function testCreateForInvalidAddressThrowsException(): void
    {
        $address = 'tcp://foo:123';

        $this->expectException(Exception::class);
        ClamAvClientFactory::create($address);
    }
}
