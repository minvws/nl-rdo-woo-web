<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

class RolloverParameters
{
    public function __construct(protected int $mappingVersion)
    {
    }

    public function getMappingVersion(): int
    {
        return $this->mappingVersion;
    }

    public function setMappingVersion(int $mappingVersion): self
    {
        $this->mappingVersion = $mappingVersion;

        return $this;
    }
}
