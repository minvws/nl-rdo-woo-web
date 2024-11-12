<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

class StorageRootPathGenerator
{
    /**
     * Returns the root path of an entity. Normally, this is /{prefix}/{suffix}, where prefix are the first two
     * characters of the SHA256 hash, and suffix is the rest of the SHA256 hash.
     */
    public function __invoke(EntityWithFileInfo $entity): string
    {
        return $this->fromUuid($entity->getId());
    }

    public function fromUuid(Uuid $id): string
    {
        $hash = hash('sha256', $id->__toString());

        $prefix = substr($hash, 0, 2);
        $suffix = substr($hash, 2);

        return sprintf('/%s/%s', $prefix, $suffix);
    }
}
