<?php

declare(strict_types=1);

namespace App\Service\Elastic;

class MappingService
{
    public function __construct(protected string $rootDir)
    {
    }

    /**
     * @return mixed[]
     */
    public function getMapping(int $version): array
    {
        $mapping = $this->readJson("mapping-v{$version}.json");

        return $mapping;
    }

    /**
     * @return mixed[]
     */
    public function getSettings(): array
    {
        $settings = $this->readJson('settings.json');

        return $settings;
    }

    public function getLatestMappingVersion(): int
    {
        $versions = $this->getMappingVersions();
        if (empty($versions)) {
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
    protected function readJson(string $filename): array
    {
        $fileData = file_get_contents($this->rootDir . '/config/elastic/' . $filename);
        if (! $fileData) {
            throw new \RuntimeException('Could not read mapping file');
        }

        $data = json_decode($fileData, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Could not decode mapping file');
        }

        return $data;
    }

    /**
     * @return int[]
     */
    protected function getMappingVersions(): array
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
