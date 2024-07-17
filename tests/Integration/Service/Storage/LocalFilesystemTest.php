<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Storage;

use App\Service\Storage\LocalFilesystem;
use App\Service\Storage\StorageRuntimeException;
use App\Tests\Integration\IntegrationTestTrait;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use phpmock\mockery\PHPMockery;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

final class LocalFilesystemTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private const TEST_NAMESPACE = 'App\\Service\\Storage';

    private vfsStreamDirectory $root;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();
        $this->logger = \Mockery::mock(LoggerInterface::class);

        // If the actual function is called before the mock was defined, the test can fail:
        PHPMockery::define(self::TEST_NAMESPACE, 'fwrite');
        PHPMockery::define(self::TEST_NAMESPACE, 'fread');
        PHPMockery::define(self::TEST_NAMESPACE, 'tempnam');
        PHPMockery::define(self::TEST_NAMESPACE, 'sys_get_temp_dir');
        PHPMockery::define(self::TEST_NAMESPACE, 'unlink');
        PHPMockery::define(self::TEST_NAMESPACE, 'uniqid');
        PHPMockery::define(self::TEST_NAMESPACE, 'mkdir');
        PHPMockery::define(self::TEST_NAMESPACE, 'rmdir');
    }

    public function testCopy(): void
    {
        $contents = 'Hello World!';

        vfsStream::create(['input.txt' => $contents], $this->root);

        $source = fopen('vfs://root/input.txt', 'r');
        Assert::resource($source, 'stream', 'The source should be a stream');

        $target = fopen('vfs://root/hello.txt', 'w');
        Assert::resource($target, 'stream', 'The target should be a stream');

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->copy($source, $target);

        $this->assertTrue($result, 'The copy operation should return true');
        $this->assertTrue($this->root->hasChild('hello.txt'));

        /** @var vfsStreamFile $child */
        $child = $this->root->getChild('hello.txt');

        $this->assertSame($contents, $child->getContent());
    }

    public function testCopyWhenFreadReturnFalse(): void
    {
        PHPMockery::mock(self::TEST_NAMESPACE, 'fread')->once()->andReturnFalse();

        $contents = 'Hello World!';

        vfsStream::create(['input.txt' => $contents], $this->root);

        $source = fopen('vfs://root/input.txt', 'r');
        Assert::resource($source, 'stream', 'The source should be a stream');

        $target = fopen('vfs://root/hello.txt', 'w');
        Assert::resource($target, 'stream', 'The target should be a stream');

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->copy($source, $target);

        $this->assertTrue($result, 'The copy operation should return true');
        $this->assertTrue($this->root->hasChild('hello.txt'));
    }

    public function testCopyWhenFwriteReturnsFalse(): void
    {
        PHPMockery::mock(self::TEST_NAMESPACE, 'fwrite')->once()->andReturnFalse();

        $this->logger->shouldReceive('error')->once()->with('Could not copy data between streams', [
            'exception' => 'Could not write data to target stream',
        ]);

        $contents = 'Hello World!';

        vfsStream::create(['input.txt' => $contents], $this->root);

        $source = fopen('vfs://root/input.txt', 'r');
        Assert::resource($source, 'stream', 'The source should be a stream');

        $target = fopen('vfs://root/hello.txt', 'w');
        Assert::resource($target, 'stream', 'The target should be a stream');

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->copy($source, $target);

        $this->assertFalse($result, 'The copy operation should return false');

        \Mockery::close();
    }

    public function testCreateTempFile(): void
    {
        $path = 'vfs://root/tmp/tempfile';
        PHPMockery::mock(self::TEST_NAMESPACE, 'sys_get_temp_dir')->once()->andReturn($tmpDir = '/foobar');
        PHPMockery::mock(self::TEST_NAMESPACE, 'tempnam')
            ->once()
            ->with($tmpDir, 'woopie')
            ->andReturnUsing(function () use ($path) {
                vfsStream::create(['tmp' => ['tempfile' => '']], $this->root);

                return $path;
            });

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->createTempFile();

        $this->assertSame($path, $result);
        $this->assertFileExists($result);
    }

    public function testCreateTempFileWhenTempnamFails(): void
    {
        PHPMockery::mock(self::TEST_NAMESPACE, 'sys_get_temp_dir')->twice()->andReturn($tmpDir = '/foobar');
        PHPMockery::mock(self::TEST_NAMESPACE, 'tempnam')->once()->with($tmpDir, 'woopie')->andReturnFalse();

        $this->logger->shouldReceive('error')->once()->with('Could not create temporary file', [
            'tempDir' => $tmpDir,
        ]);

        $fs = new LocalFilesystem($this->logger);
        $path = $fs->createTempFile();

        $this->assertFalse($path);
    }

    public function testCreateTempDir(): void
    {
        vfsStream::create(['foobar' => []]);

        PHPMockery::mock(self::TEST_NAMESPACE, 'sys_get_temp_dir')->once()->andReturn($tmpDir = 'vfs://root/foobar');
        PHPMockery::mock(self::TEST_NAMESPACE, 'uniqid')
            ->once()
            ->with('woopie_', true)
            ->andReturn($uniqueId = 'woopie_foobar');

        $path = $tmpDir . '/' . $uniqueId;

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->createTempDir();

        $this->assertSame($path, $result);
        $this->assertDirectoryExists($result);
    }

    public function testCreateTempDirWhenCreatingDirFails(): void
    {
        vfsStream::create(['foobar' => []]);

        PHPMockery::mock(self::TEST_NAMESPACE, 'sys_get_temp_dir')->once()->andReturn($tmpDir = 'vfs://root/foobar');
        PHPMockery::mock(self::TEST_NAMESPACE, 'uniqid')
            ->once()
            ->with('woopie_', true)
            ->andReturn($uniqueId = 'woopie_foobar');
        PHPMockery::mock(self::TEST_NAMESPACE, 'mkdir')->once()->andReturnFalse();

        $path = $tmpDir . '/' . $uniqueId;

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Could not create temporary dir', [
                'tempDir' => $path,
            ]);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->createTempDir();

        $this->assertFalse($result);
        $this->assertDirectoryDoesNotExist($path);
    }

    public function testDeleteDirectory(): void
    {
        vfsStream::create([
            'dir' => [
                'file.txt' => '',
                'subdir' => [
                    'file.txt' => '',
                ],
            ],
        ], $this->root);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->deleteDirectory('vfs://root/dir');

        $this->assertTrue($result, 'The delete operation should return true');
        $this->assertDirectoryDoesNotExist('vfs://root/dir');
    }

    public function testDeleteDirectoryWhenGivenAFile(): void
    {
        vfsStream::create([
            'dir' => [
                'file.txt' => '',
                'subdir' => [
                    'file.txt' => '',
                ],
            ],
        ], $this->root);

        $this->expectExceptionObject(new StorageRuntimeException('"vfs://root/dir/file.txt" must be a directory'));

        $fs = new LocalFilesystem($this->logger);
        $fs->deleteDirectory('vfs://root/dir/file.txt');
    }

    public function testDeleteDirectoryWhenRmdirFails(): void
    {
        vfsStream::create([
            'dir' => [
                'file.txt' => '',
                'subdir' => [
                    'file.txt' => '',
                ],
            ],
        ], $this->root);

        PHPMockery::mock(self::TEST_NAMESPACE, 'rmdir')->atLeast()->once()->andReturnFalse();
        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Could not delete directory', \Mockery::on(function (array $context) {
                if (! isset($context['dirPath']) || ! is_string($context['dirPath'])) {
                    return false;
                }

                return str_starts_with($context['dirPath'], 'vfs://root/dir');
            }));

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->deleteDirectory('vfs://root/dir');

        $this->assertFalse($result, 'The delete operation should return false');
        $this->assertDirectoryExists('vfs://root/dir');
    }

    public function testDeleteFile(): void
    {
        vfsStream::create(['file.txt' => ''], $this->root);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->deleteFile('vfs://root/file.txt');

        $this->assertTrue($result, 'The delete operation should return true');
        $this->assertFalse($this->root->hasChild('file.txt'));
    }

    public function testDeleteOnNonExistingFile(): void
    {
        $fs = new LocalFilesystem($this->logger);
        $result = $fs->deleteFile('vfs://root/file.txt');

        $this->assertTrue($result, 'The delete operation should return true');
    }

    public function testDeleteFileFails(): void
    {
        PHPMockery::mock(self::TEST_NAMESPACE, 'unlink')->once()->andReturnFalse();

        $this->logger->shouldReceive('error')->once()->with('Could not delete local file', [
            'local_path' => 'vfs://root/file.txt',
        ]);

        vfsStream::create(['file.txt' => ''], $this->root);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->deleteFile('vfs://root/file.txt');

        $this->assertFalse($result, 'The delete operation should return true');
        $this->assertTrue($this->root->hasChild('file.txt'));
    }

    public function testCreateStream(): void
    {
        vfsStream::create(['file.txt' => $content = 'my content'], $this->root);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->createStream('vfs://root/file.txt', 'r');

        $this->assertIsResource($result);
        $this->assertSame('stream', get_resource_type($result));
        $this->assertSame($content, stream_get_contents($result));
    }

    public function testFailedCreateStream(): void
    {
        $this->logger->shouldReceive('error')->once()->with('Could not open local file file', [
            'local_path' => $path = 'vfs://root/file.txt', // file does not exist
            'mode' => $mode = 'r',
        ]);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->createStream($path, $mode);

        $this->assertFalse($result);
    }
}
