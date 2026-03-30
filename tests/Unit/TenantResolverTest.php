<?php

declare(strict_types=1);

namespace Shared\Tests\Unit;

use InvalidArgumentException;
use Shared\TenantResolver;
use ValueError;

class TenantResolverTest extends UnitTestCase
{
    public function testFromContext(): void
    {
        $tenantId = TenantResolver::fromContext([
            'HTTP_HOST_TO_TENANT_MAPPING' => 'foobar.com=minvws,example.com=minfin',
            'HTTP_HOST' => 'example.com',
        ]);

        $this->assertSame('minfin', $tenantId->value);
    }

    public function testFromContextWithMappingMissing(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('HTTP_HOST_TO_TENANT_MAPPING is required in context to resolve tenant ID'));

        TenantResolver::fromContext([
            'HTTP_HOST' => 'example.com',
        ]);
    }

    public function testFromContextWithEmptyMappingValue(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException('HTTP_HOST_TO_TENANT_MAPPING must contain non-empty mappings in the format "host=tenantId"'),
        );

        TenantResolver::fromContext([
            'HTTP_HOST_TO_TENANT_MAPPING' => 'foobar.com=minvws,',
            'HTTP_HOST' => 'example.com',
        ]);
    }

    public function testFromContextWithInvalidMappingFormat(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid mapping "acme.com:minfin", expected format "host=tenantId"'));

        TenantResolver::fromContext([
            'HTTP_HOST_TO_TENANT_MAPPING' => 'foobar.com=minvws,acme.com:minfin',
            'HTTP_HOST' => 'example.com',
        ]);
    }

    public function testFromContextWithEmptyHostPartInMapping(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Host cannot be empty in mapping "=minfin"'));

        TenantResolver::fromContext([
            'HTTP_HOST_TO_TENANT_MAPPING' => 'foobar.com=minvws,=minfin',
            'HTTP_HOST' => 'example.com',
        ]);
    }

    public function testFromContextWithEmptyTenantIdPartInMapping(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Tenant ID cannot be empty in mapping "foobar.com="'));

        TenantResolver::fromContext([
            'HTTP_HOST_TO_TENANT_MAPPING' => 'foobar.com=,example.com=',
            'HTTP_HOST' => 'example.com',
        ]);
    }

    public function testFromContextWithMissingHost(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('HTTP_HOST is required in context to resolve tenant ID'));

        TenantResolver::fromContext([
            'HTTP_HOST_TO_TENANT_MAPPING' => 'foobar.com=minvws,example.com=minfin',
        ]);
    }

    public function testFromContextWithInvalidTenantId(): void
    {
        $this->expectExceptionObject(new ValueError('"invalid" is not a valid backing value for enum Shared\TenantId'));

        TenantResolver::fromContext([
            'HTTP_HOST_TO_TENANT_MAPPING' => 'foobar.com=minvws,example.com=invalid',
            'HTTP_HOST' => 'example.com',
        ]);
    }
}
