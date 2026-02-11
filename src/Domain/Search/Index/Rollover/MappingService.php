<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use RuntimeException;

use function file_get_contents;
use function glob;
use function in_array;
use function is_array;
use function is_readable;
use function json_decode;
use function max;
use function preg_match;

readonly class MappingService
{
    public function __construct(
        private string $rootDir,
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getMapping(int $version): array
    {
        return $this->readJson("mapping-v{$version}.json");
    }

    /**
     * @return mixed[]
     */
    public function getSettings(): array
    {
        return $this->readJson('settings.json');
    }

    public function getLatestMappingVersion(): int
    {
        $versions = $this->getMappingVersions();
        if ($versions === []) {
            return -1;
        }

        return max($versions);
    }

    public function isValidMappingVersion(int $version): bool
    {
        $versions = $this->getMappingVersions();

        return in_array($version, $versions);
    }

    /**
     * @return mixed[]
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

    /**
     * @return int[]
     */
    private function getMappingVersions(): array
    {
        $versions = [];
        $dir = $this->rootDir . '/config/elastic/';
        $filenames = glob($dir . 'mapping-v*.json');
        if ($filenames === false) {
            return [];
        }

        foreach ($filenames as $filename) {
            if (preg_match('/mapping-v(\d+)\.json/', $filename, $matches)) {
                $version = (int) $matches[1];
                $versions[] = $version;
            }
        }

        return $versions;
    }
}
