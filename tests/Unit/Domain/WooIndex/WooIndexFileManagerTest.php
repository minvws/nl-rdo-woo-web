<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex;

use App\Domain\WooIndex\DiWooInvalidArgumentException;
use App\Domain\WooIndex\WooIndexFileManager;
use App\Domain\WooIndex\WooIndexFinderFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class WooIndexFileManagerTest extends UnitTestCase
{
    private Filesystem&MockInterface $filesystem;

    private LoggerInterface&MockInterface $logger;

    private WooIndexFinderFactory&MockInterface $wooIndexFinderFactory;

    private Finder&MockInterface $finder;

    private WooIndexFileManager $wooIndexFileManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = \Mockery::mock(Filesystem::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->wooIndexFinderFactory = \Mockery::mock(WooIndexFinderFactory::class);
        $this->finder = \Mockery::mock(Finder::class);

        $this->wooIndexFileManager = new WooIndexFileManager(
            $this->filesystem,
            $this->logger,
            $this->wooIndexFinderFactory,
            '/var/www/html/public/sitemap-woo-index',
        );
    }

    public function testPublishLogsMessageWhenItThrowsAnIOException(): void
    {
        $this->filesystem
            ->shouldReceive('mirror')
            ->andThrow(new IOException($exceptionMessage = 'my exception', path: $exceptionPath = 'my path'));

        $this->logger
            ->shouldReceive('error')
            ->with(
                'Could not move generated sitemap',
                [
                    'exception' => $exceptionMessage,
                    'path' => $exceptionPath,
                ],
            );

        $result = $this->wooIndexFileManager->publish('my source');

        $this->assertFalse($result);
    }

    #[DataProvider('getCleanupPublishedData')]
    public function testCleanupPublished(int $treshold): void
    {
        $this->expectExceptionObject(DiWooInvalidArgumentException::invalidTreshold($treshold));

        $this->wooIndexFileManager->cleanupPublished($treshold);
    }

    public function testCleanupPublishedWithAlternativeTreshold(): void
    {
        $iterable = new \ArrayIterator([
            $this->getMockedFile('file1'),
            $this->getMockedFile('file2'),
            $this->getMockedFile('file3'),
            $this->getMockedFile('file4'),
            $this->getMockedFile('file5'),
            $this->getMockedFile('file6'),
            $this->getMockedFile('file7'),
            $this->getMockedFile('file8'),
        ]);

        $this->finder->shouldReceive('getIterator')->andReturn($iterable);

        $this->wooIndexFinderFactory->shouldReceive('create')->andReturn($this->finder);

        $this->filesystem->shouldNotReceive('remove')->with('file1');
        $this->filesystem->shouldNotReceive('remove')->with('file2');
        $this->filesystem->shouldNotReceive('remove')->with('file3');
        $this->filesystem->shouldNotReceive('remove')->with('file4');
        $this->filesystem->shouldNotReceive('remove')->with('file5');

        $this->filesystem->shouldReceive('remove')->with('file6')->once();
        $this->filesystem->shouldReceive('remove')->with('file7')->once();
        $this->filesystem->shouldReceive('remove')->with('file8')->once();

        $this->wooIndexFileManager->cleanupPublished(5);
    }

    public function testCleanupPublishedLogsMessageWhenItThrowsAnIOException(): void
    {
        $this->filesystem
            ->shouldReceive('remove')
            ->andThrow(new IOException($exceptionMessage = 'my exception', path: $exceptionPath = 'my path'));

        $this->finder
            ->shouldReceive('getIterator')
            ->andReturn(new \ArrayIterator([$this->getMockedFile('file1'), $this->getMockedFile('file2')]));

        $this->wooIndexFinderFactory->shouldReceive('create')->andReturn($this->finder);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with(
                'Could not remove generated sitemap',
                [
                    'exception' => $exceptionMessage,
                    'path' => $exceptionPath,
                ],
            );

        $this->wooIndexFileManager->cleanupPublished(1);
    }

    /**
     * @return array<string,array{treshold:int}>
     */
    public static function getCleanupPublishedData(): array
    {
        return [
            'zero' => [
                'treshold' => 0,
            ],
            'negative number' => [
                'treshold' => -10,
            ],
        ];
    }

    private function getMockedFile(string $pathName): \SplFileInfo&MockInterface
    {
        $file = \Mockery::mock(\SplFileInfo::class);
        $file->shouldReceive('getPathname')->andReturn($pathName);

        return $file;
    }
}
