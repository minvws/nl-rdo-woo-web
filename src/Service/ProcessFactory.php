<?php

declare(strict_types=1);

namespace Shared\Service;

use Symfony\Component\Process\Process;

readonly class ProcessFactory
{
    /**
     * @param array<int,string> $command
     */
    public function create(array $command): Process
    {
        return new Process($command);
    }
}
