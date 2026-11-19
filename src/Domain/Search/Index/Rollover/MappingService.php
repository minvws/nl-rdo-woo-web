<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use RuntimeException;

use function file_get_contents;
use function is_array;
use function is_int;
use function is_readable;
use function json_decode;

readonly class MappingService
{
    public function __construct(
        private string $rootDir,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getMapping(): array
    {
        return $this->readJson('mapping.json');
    }

    public function getMappingVersion(): int
    {
        $mapping = $this->getMapping();
        $metadata = $mapping['_meta'] ?? null;
        if (! is_array($metadata) || ! is_int($metadata['version'] ?? null)) {
            throw new RuntimeException('Mapping version is missing or invalid');
        }

        return $metadata['version'];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getSettings(): array
    {
        return $this->readJson('settings.json');
    }

    /**
     * @return array<array-key, mixed>
     */
    private function readJson(string $filename): array
    {
        $filePath = $this->rootDir . '/config/elastic/' . $filename;
        if (! is_readable($filePath)) {
            throw new RuntimeException('Could not read mapping file');
        }

        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException('Could not read mapping file');
        }

        $data = json_decode($data, true);
        if (! is_array($data)) {
            throw new RuntimeException('Could not decode mapping file');
        }

        return $data;
    }
}
