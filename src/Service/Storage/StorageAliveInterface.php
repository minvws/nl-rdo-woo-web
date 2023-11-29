<?php

declare(strict_types=1);

namespace App\Service\Storage;

interface StorageAliveInterface
{
    public function isAlive(): bool;
}
