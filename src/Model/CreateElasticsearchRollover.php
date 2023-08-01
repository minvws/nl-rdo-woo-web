<?php

declare(strict_types=1);

namespace App\Model;

class CreateElasticsearchRollover
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
