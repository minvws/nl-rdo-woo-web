<?php

declare(strict_types=1);

namespace Shared;

use Webmozart\Assert\Assert;

use function array_map;
use function array_reduce;
use function explode;
use function sprintf;
use function trim;

final readonly class TenantResolver
{
    /**
     * @param array<array-key,mixed> $context
     */
    public static function fromContext(array $context): TenantId
    {
        $httpHostToTenantMapping = $context['HTTP_HOST_TO_TENANT_MAPPING'] ?? null;
        Assert::string($httpHostToTenantMapping, 'HTTP_HOST_TO_TENANT_MAPPING is required in context to resolve tenant ID');

        $explodedMapping = explode(',', $httpHostToTenantMapping);
        $explodedNonEmptyMapping = array_map(trim(...), $explodedMapping);
        Assert::allStringNotEmpty(
            $explodedNonEmptyMapping,
            'HTTP_HOST_TO_TENANT_MAPPING must contain non-empty mappings in the format "host=tenantId"',
        );

        $mappings = array_reduce($explodedNonEmptyMapping, self::tenantMapping(...), []);

        $host = $context['HTTP_HOST'] ?? null;
        Assert::stringNotEmpty($host, 'HTTP_HOST is required in context to resolve tenant ID');

        $tenantId = $mappings[$host] ?? null;
        Assert::stringNotEmpty(
            $tenantId,
            sprintf('No tenant ID found for HTTP host "%s". Please check your HTTP_HOST_TO_TENANT_MAPPING environment variable.', $host),
        );

        return TenantId::fromString($tenantId);
    }

    /**
     * @param array<string,string> $result
     * @param string $mapping Should be in the format of "<HOST_NAME>=<TENANT_ID>"
     *
     * @return array<string,string>
     */
    private static function tenantMapping(array $result, string $mapping): array
    {
        $parts = explode('=', $mapping);
        Assert::count($parts, 2, sprintf('Invalid mapping "%s", expected format "host=tenantId"', $mapping));

        $host = trim($parts[0]);
        $tenantId = trim($parts[1]);

        Assert::stringNotEmpty($host, sprintf('Host cannot be empty in mapping "%s"', $mapping));
        Assert::stringNotEmpty($tenantId, sprintf('Tenant ID cannot be empty in mapping "%s"', $mapping));

        $result[$host] = $tenantId;

        return $result;
    }
}
