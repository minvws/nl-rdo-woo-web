<?php

declare(strict_types=1);

namespace Shared\Service;

use function hash;
use function serialize;

class ObjectHasher
{
    public function get(object $object): string
    {
        return hash('md5', serialize($object));
    }

    public function isEqual(object $object, string $hash): bool
    {
        return $this->get($object) === $hash;
    }

    public function isNotEqual(object $object, string $hash): bool
    {
        return ! $this->isEqual($object, $hash);
    }
}
