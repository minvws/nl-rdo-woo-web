<?php

declare(strict_types=1);

namespace App\Domain\Upload\AntiVirus;

use Socket\Raw\Factory;
use Xenolope\Quahog\Client;

class ClamAvClientFactory
{
    public static function create(string $address): Client
    {
        $socket = (new Factory())->createClient($address);

        return new Client($socket);
    }
}
