<?php

declare(strict_types=1);

namespace Shared\Service\Storage;

use function hash;
use function random_bytes;
use function sprintf;

trait HasAlive
{
    public function isAlive(): bool
    {
        $hash = hash('sha256', random_bytes(32));
        $location = sprintf('healthcheck.%s', $hash);

        if (! $this->getStorage()->write($location, contents: $hash)) {
            return false;
        }

        $content = $this->getStorage()->read($location);
        if ($content === false) {
            return false;
        }

        if (! $this->getStorage()->delete($location)) {
            return false;
        }

        return $content === $hash;
    }

    abstract protected function getStorage(): RemoteFilesystem;
}
