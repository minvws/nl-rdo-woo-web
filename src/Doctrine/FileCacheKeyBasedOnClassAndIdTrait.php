<?php

declare(strict_types=1);

namespace Shared\Doctrine;

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
