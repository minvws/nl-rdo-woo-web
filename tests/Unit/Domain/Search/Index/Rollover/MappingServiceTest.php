<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\Rollover\MappingService;
use App\Tests\Unit\UnitTestCase;

class MappingServiceTest extends UnitTestCase
{
    private MappingService $mappingService;

    public function setUp(): void
    {
        $this->mappingService = new MappingService(__DIR__);

        parent::setUp();
    }

    public function testGetMapping(): void
    {
        $this->assertMatchesJsonSnapshot($this->mappingService->getMapping(1));
    }

    public function testGetMappingThrowsExceptionForNonExistingVersion(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->mappingService->getMapping(999);
    }

    public function testGetMappingThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->mappingService->getMapping(2);
    }

    public function testGetSettings(): void
    {
        $this->assertMatchesJsonSnapshot($this->mappingService->getSettings());
    }

    public function testGetLatestMappingVersion(): void
    {
        $this->assertEquals(3, $this->mappingService->getLatestMappingVersion());
    }

    public function testIsValidMappingVersion(): void
    {
        $this->assertTrue($this->mappingService->isValidMappingVersion(1));
        $this->assertTrue($this->mappingService->isValidMappingVersion(2));
        $this->assertTrue($this->mappingService->isValidMappingVersion(3));
        $this->assertFalse($this->mappingService->isValidMappingVersion(4));
    }
}
