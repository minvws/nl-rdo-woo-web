<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex;

use App\Domain\WooIndex\WooIndexFileManager;
use App\Tests\Integration\IntegrationTestTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WooIndexFileManagerTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    private WooIndexFileManager $wooIndexFileManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        $this->wooIndexFileManager = self::getContainer()->get(WooIndexFileManager::class);
    }

    public function testPublish(): void
    {
        vfsStream::create([
            'tmp' => [
                'woopie_679ea0957508b6.92850051' => [
                    '20250101_120100_000000__random-string' => [
                        'sitemap-index.xml' => 'sitemap-index',
                        'sitemap-00001.xml' => 'sitemap-00001',
                        'sitemap-00002.xml' => 'sitemap-00002',
                        'sitemap-00003.xml' => 'sitemap-00003',
                        'sitemap-00004.xml' => 'sitemap-00004',
                    ],
                ],
            ],
            'var' => [
                'www' => [
                    'html' => [
                        'public' => [
                            'sitemap' => [
                                'woo-index' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $source = vfsStream::url('root') . '/tmp/woopie_679ea0957508b6.92850051/20250101_120100_000000__random-string';

        $path = $this->wooIndexFileManager->publish($source);

        $this->assertNotFalse($path);
        $this->assertMatchesYamlSnapshot($this->inspectVfs());
    }

    public function testGetLastPublished(): void
    {
        vfsStream::create([
            'var' => [
                'www' => [
                    'html' => [
                        'public' => [
                            'sitemap' => [
                                'woo-index' => [
                                    '20251010_invalid_formmatted_dir' => [],
                                    '20250110_120100_000000__aaaaaaaa' => [
                                        '20250401_230100_000001__ffffffff' => [],
                                    ],
                                    '20250301_230100_000000__bbbbbbbb' => 'I am not a directory',
                                    '20250109_120100_000000__cccccccc' => [],
                                    '20250301_230100_000001__dddddddd' => [],
                                    '20250101_120100_000000__eeeeeeee' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->wooIndexFileManager->getLastPublished();

        $this->assertSame($result, '20250301_230100_000001__dddddddd');
    }

    public function testGetLastPublishedReturnsNullIfNoWooIndexPublishedYet(): void
    {
        vfsStream::create([
            'var' => [
                'www' => [
                    'html' => [
                        'public' => [
                            'sitemap' => [
                                'woo-index' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertNull($this->wooIndexFileManager->getLastPublished());
    }

    public function testCleanupPublished(): void
    {
        vfsStream::create([
            'var' => [
                'www' => [
                    'html' => [
                        'public' => [
                            'sitemap' => [
                                'woo-index' => [
                                    '20251010_invalid_formmatted_dir' => [
                                        'file_one' => 'file contents',
                                        'file_two' => 'file contents',
                                    ],
                                    '20250110_120100_000000__aaaaaaaa' => [
                                        'file_one' => 'file contents',
                                        'file_two' => 'file contents',
                                    ],
                                    '20250301_230100_000000__bbbbbbbb' => 'I am not a directory',
                                    '20250109_120100_000000__cccccccc' => [
                                        'file_one' => 'file contents',
                                        'file_two' => 'file contents',
                                    ],
                                    '20250301_230100_000001__dddddddd' => [
                                        'file_one' => 'file contents',
                                        'file_two' => 'file contents',
                                    ],
                                    '20250101_120100_000000__eeeeeeee' => [
                                        'file_one' => 'file contents',
                                        'file_two' => 'file contents',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $lastPublished = $this->wooIndexFileManager->getLastPublished();
        $this->wooIndexFileManager->cleanupPublished(1);

        $basepath = $this->root->url() . '/var/www/html/public/sitemap/woo-index';

        // This directory was not removed because its format is invalid:
        $this->assertDirectoryExists(sprintf('%s/%s', $basepath, '20251010_invalid_formmatted_dir'));

        // This file wat not removed because it only removes directories:
        $this->assertFileExists(sprintf('%s/%s', $basepath, '20250301_230100_000000__bbbbbbbb'));

        // These directories are removed because the name is in a valid format:
        $this->assertDirectoryDoesNotExist(sprintf('%s/%s', $basepath, '20250110_120100_000000__aaaaaaaa'));
        $this->assertDirectoryDoesNotExist(sprintf('%s/%s', $basepath, '20250109_120100_000000__cccccccc'));
        $this->assertDirectoryDoesNotExist(sprintf('%s/%s', $basepath, '20250101_120100_000000__eeeeeeee'));

        // This is the last published WooIndex:
        $this->assertDirectoryExists(sprintf('%s/%s', $basepath, '20250301_230100_000001__dddddddd'));
        $this->assertSame('20250301_230100_000001__dddddddd', $lastPublished);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function inspectVfs(): array
    {
        /** @var vfsStreamStructureVisitor $vfsVisitor */
        $vfsVisitor = vfsStream::inspect(new vfsStreamStructureVisitor());

        return $vfsVisitor->getStructure();
    }
}
