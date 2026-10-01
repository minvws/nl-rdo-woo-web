<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain;

use Shared\TenantId;
use Shared\Tests\Unit\UnitTestCase;

use function array_column;
use function dirname;
use function file_get_contents;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class TenantsJsonTest extends UnitTestCase
{
    public function testTenantsJsonMatchesTheEnum(): void
    {
        $path = dirname(__DIR__, 3) . '/tenants/tenants.json';

        $contents = file_get_contents($path);
        $this->assertIsString($contents, "Could not read $path");

        $this->assertSame(
            array_column(TenantId::cases(), 'value'),
            json_decode($contents, true, flags: JSON_THROW_ON_ERROR),
            'tenants.json is out of sync with the TenantId enum. It feeds the Vite entries for the '
            . 'tenant stylesheets and the ALL_TENANTS var in the Taskfile, so a missing tenant '
            . 'means its public site loads without CSS. List every tenant, in the same order as '
            . 'the enum cases.',
        );
    }
}
