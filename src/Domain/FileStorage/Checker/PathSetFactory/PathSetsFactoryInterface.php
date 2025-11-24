<?php

declare(strict_types=1);

namespace Shared\Domain\FileStorage\Checker\PathSetFactory;

use Shared\Domain\FileStorage\Checker\PathSet;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('domain.filestorage.pathsetsfactory')]
interface PathSetsFactoryInterface
{
    /**
     * @return \Generator<PathSet>
     */
    public function getPathSets(): \Generator;
}
