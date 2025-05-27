<?php

declare(strict_types=1);

namespace App\Domain\FileStorage\Checker\PathSetFactory;

use App\Domain\FileStorage\Checker\PathSet;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('domain.filestorage.pathsetsfactory')]
interface PathSetsFactoryInterface
{
    /**
     * @return \Generator<PathSet>
     */
    public function getPathSets(): \Generator;
}
