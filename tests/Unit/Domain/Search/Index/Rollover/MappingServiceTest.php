<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Rollover;

use RuntimeException;
use Shared\Domain\Search\Index\Rollover\MappingService;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function dirname;

class MappingServiceTest extends UnitTestCase
{
    private MappingService $mappingService;

    protected function setUp(): void
    {
        $this->mappingService = new MappingService(__DIR__);

        parent::setUp();
    }

    public function testGetMapping(): void
    {
        $this->assertMatchesJsonSnapshot($this->mappingService->getMapping(1));
    }

    public function testSubjectMappingSupportsVisibleContentTreeMetadata(): void
    {
        $mapping = new MappingService(dirname(__DIR__, 6))->getMapping(32);
        Assert::keyExists($mapping, '_meta');
        Assert::isArray($mapping['_meta']);
        $metadata = $mapping['_meta'];
        Assert::keyExists($metadata, 'version');

        self::assertSame(32, $metadata['version']);

        Assert::keyExists($mapping, 'properties');
        Assert::isArray($mapping['properties']);
        $properties = $mapping['properties'];
        Assert::keyExists($properties, 'content_tree');
        Assert::isArray($properties['content_tree']);
        self::assertSame([
            'type' => 'nested',
            'properties' => [
                'title' => ['type' => 'text', 'analyzer' => 'dutch'],
                'body' => ['type' => 'text', 'analyzer' => 'dutch'],
            ],
        ], $properties['content_tree']);
        Assert::keyExists($properties, 'has_visible_content_tree');
        self::assertSame(
            ['type' => 'boolean'],
            $properties['has_visible_content_tree'],
        );
    }

    public function testGetMappingThrowsExceptionForNonExistingVersion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->mappingService->getMapping(999);
    }

    public function testSubjectMappingPreservesPreviousFieldsExceptRetiredReferenceName(): void
    {
        $mappingService = new MappingService(dirname(__DIR__, 6));
        $previousMapping = $mappingService->getMapping(31);
        $mapping = $mappingService->getMapping(32);
        Assert::isArray($previousMapping['properties']);
        Assert::isArray($mapping['properties']);
        $previousProperties = $previousMapping['properties'];
        $properties = $mapping['properties'];

        self::assertArrayNotHasKey('referred_document_nrs', $properties);
        unset($previousProperties['referred_document_nrs']);
        unset($properties['content_tree'], $properties['has_visible_content_tree']);

        self::assertSame($previousProperties, $properties);
        foreach (['title', 'summary'] as $field) {
            Assert::isArray($properties[$field]);
            self::assertSame('content_for_suggestions', $properties[$field]['copy_to']);
        }
    }

    public function testGetMappingThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(RuntimeException::class);
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

    public function testGetLatestMappingVersionWithNoMappings(): void
    {
        $mappingService = new MappingService('non-existing');
        $this->assertEquals(-1, $mappingService->getLatestMappingVersion());
    }

    public function testIsValidMappingVersion(): void
    {
        $this->assertTrue($this->mappingService->isValidMappingVersion(1));
        $this->assertTrue($this->mappingService->isValidMappingVersion(2));
        $this->assertTrue($this->mappingService->isValidMappingVersion(3));
        $this->assertFalse($this->mappingService->isValidMappingVersion(4));
    }
}
