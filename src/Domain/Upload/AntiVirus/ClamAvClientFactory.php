<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\AntiVirus;

use Socket\Raw\Factory;
use Xenolope\Quahog\Client;

readonly class ClamAvClientFactory
{
    public function __construct(
        private string $address,
    ) {
    }

    public function getClient(): Client
    {
        $socket = (new Factory())->createClient($this->address);

        return new Client($socket);
    }
}
