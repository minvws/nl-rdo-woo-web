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
        $mapping = $this->mappingService->getMapping();

        Assert::keyExists($mapping, '_meta');
        Assert::isArray($mapping['_meta']);
        Assert::keyExists($mapping['_meta'], 'version');
        self::assertSame(3, $mapping['_meta']['version']);
    }

    public function testSubjectMappingSupportsVisibleContentTreeMetadata(): void
    {
        $mapping = new MappingService(dirname(__DIR__, 6))->getMapping();
        Assert::keyExists($mapping, '_meta');
        Assert::isArray($mapping['_meta']);
        $metadata = $mapping['_meta'];
        Assert::keyExists($metadata, 'version');

        self::assertSame(33, $metadata['version']);

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

    public function testGetMappingThrowsExceptionForNonExistingRootDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        new MappingService('non-existing')->getMapping();
    }

    public function testSubjectMappingPreservesPreviousFieldsExceptRetiredReferenceName(): void
    {
        $mappingService = new MappingService(dirname(__DIR__, 6));
        $mapping = $mappingService->getMapping();
        Assert::isArray($mapping['properties']);
        $properties = $mapping['properties'];

        foreach (['title', 'summary'] as $field) {
            Assert::isArray($properties[$field]);
            self::assertSame('content_for_suggestions', $properties[$field]['copy_to']);
        }
    }

    public function testMappingIncludesLinksAsKeywords(): void
    {
        $mapping = new MappingService(dirname(__DIR__, 6))->getMapping();
        Assert::keyExists($mapping, 'properties');
        Assert::isArray($mapping['properties']);
        Assert::keyExists($mapping['properties'], 'links');

        self::assertSame(
            ['type' => 'keyword'],
            $mapping['properties']['links'],
        );
    }

    public function testGetMappingVersion(): void
    {
        self::assertSame(3, $this->mappingService->getMappingVersion());
    }

    public function testGetSettings(): void
    {
        $this->assertMatchesJsonSnapshot($this->mappingService->getSettings());
    }
}
