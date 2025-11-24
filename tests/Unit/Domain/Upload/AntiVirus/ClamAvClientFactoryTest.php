<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\AntiVirus;

use Shared\Domain\Upload\AntiVirus\ClamAvClientFactory;
use Shared\Tests\Unit\UnitTestCase;
use Socket\Raw\Exception;

final class ClamAvClientFactoryTest extends UnitTestCase
{
    public function testCreateForInvalidAddressThrowsException(): void
    {
        $address = 'tcp://foo:123';

        $factory = new ClamAvClientFactory($address);

        $this->expectException(Exception::class);
        $factory->getClient();
    }
}
