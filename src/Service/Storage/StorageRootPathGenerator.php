<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\EntityWithFileInfo;

class StorageRootPathGenerator
{
    /**
     * Returns the root path of an entity. Normally, this is /{prefix}/{suffix}, where prefix are the first two
     * characters of the SHA256 hash, and suffix is the rest of the SHA256 hash.
     */
    public function __invoke(EntityWithFileInfo $entity): string
    {
        $documentId = (string) $entity->getId();
        $hash = hash('sha256', $documentId);

        $prefix = substr($hash, 0, 2);
        $suffix = substr($hash, 2);

        return sprintf('/%s/%s', $prefix, $suffix);
    }
}