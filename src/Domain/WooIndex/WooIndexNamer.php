<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

use Carbon\CarbonImmutable;
use Symfony\Component\String\ByteString;

readonly class WooIndexNamer
{
    /**
     * i.e.: "20250110_120100_123456__random-string".
     */
    public const RUN_ID_REGEX = '/^\d{8}_\d{6}_\d{6}__[a-zA-Z\-_]+$/';

    public function getWooIndexRunId(?string $pathSuffix = null): string
    {
        return sprintf('%s__%s', CarbonImmutable::now()->format('Ymd_hms_u'), $pathSuffix ?? $this->getRandomRunIdSuffix());
    }

    public function getSitemapName(int $sitemapNumber): string
    {
        return sprintf('sitemap-%05d.xml', $sitemapNumber);
    }

    public function getSitemapIndexName(): string
    {
        return 'sitemap-index.xml';
    }

    public function joinPaths(int|string|null ...$paths): string
    {
        $paths = array_filter($paths);

        if ($paths === []) {
            return '';
        }

        $paths = array_values($paths);

        $protocol = '';
        // Check if it has a protocol
        if ($paths[0] !== null && preg_match('#^[a-zA-Z][a-zA-Z\d+\-.]*://#', (string) $paths[0])) {
            [$protocol, $remainingPath] = explode('://', (string) $paths[0], 2);
            $protocol .= '://';

            $paths[0] = $remainingPath;
        }

        return $protocol . preg_replace('+/{2,}+', '/', implode('/', $paths));
    }

    protected function getRandomRunIdSuffix(): string
    {
        return ByteString::fromRandom(8)->__toString();
    }
}
