<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use function intval;
use function strrpos;
use function substr;

trait FileCacheKeyBasedOnClassAndIdTrait
{
    public function getFileCacheKey(): string
    {
        $fqn = static::class;
        $lastBackslash = intval(strrpos($fqn, '\\'));
        $classBasename = substr($fqn, $lastBackslash + 1);

        return $classBasename . '-' . $this->id->toBase58();
    }
}
