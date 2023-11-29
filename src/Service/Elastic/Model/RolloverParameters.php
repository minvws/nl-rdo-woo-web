<?php

declare(strict_types=1);

namespace App\Service\Elastic\Model;

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
