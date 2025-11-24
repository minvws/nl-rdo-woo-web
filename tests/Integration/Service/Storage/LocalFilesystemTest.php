<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Storage;

use Mockery\MockInterface;
use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Psr\Log\LoggerInterface;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Service\Storage\StorageRuntimeException;
use Shared\Tests\Integration\Service\Storage\Streams\FailingReadStreamWrapper;
use Shared\Tests\Integration\Service\Storage\Streams\FailingWriteStreamWrapper;
use Shared\Tests\Integration\SharedWebTestCase;
use Webmozart\Assert\Assert;

final class LocalFilesystemTest extends SharedWebTestCase
{
    private vfsStreamDirectory $root;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();
        $this->logger = \Mockery::mock(LoggerInterface::class);
    }

    public function testCopy(): void
    {
        $contents = 'Hello World!';

        vfsStream::create(['input.txt' => $contents], $this->root);

        $source = \fopen('vfs://root/input.txt', 'r');
        Assert::resource($source, 'stream', 'The source should be a stream');

        $target = \fopen('vfs://root/hello.txt', 'w');
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
        FailingReadStreamWrapper::register();

        $source = \fopen(FailingReadStreamWrapper::getPath('input.txt'), 'r');
        Assert::resource($source, 'stream', 'The source should be a stream');

        $target = \fopen('vfs://root/hello.txt', 'w');
        Assert::resource($target, 'stream', 'The target should be a stream');

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->copy($source, $target);

        $this->assertTrue($result, 'The copy operation should return true');
        $this->assertTrue($this->root->hasChild('hello.txt'));
    }

    public function testCopyWhenFwriteReturnsFalse(): void
    {
        $this->logger->shouldReceive('error')->once()->with('Could not copy data between streams', [
            'exception' => 'Could not write data to target stream',
        ]);

        $contents = 'Hello World!';

        vfsStream::create(['input.txt' => $contents], $this->root);

        $source = \fopen('vfs://root/input.txt', 'r');
        Assert::resource($source, 'stream', 'The source should be a stream');

        FailingWriteStreamWrapper::register();
        $target = \fopen(FailingWriteStreamWrapper::getPath('hello.txt'), 'w');
        Assert::resource($target, 'stream', 'The target should be a stream');

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->copy($source, $target);

        $this->assertFalse($result, 'The copy operation should return false');

        \Mockery::close();
    }

    public function testCreateTempFile(): void
    {
        $tmpDir = vfsStream::newDirectory('tmp')->at($this->root);
        $tmpFile = vfsStream::newFile('tempfile')->at($tmpDir);

        $fs = $this->getPartialLocalFilesystem();
        $fs->shouldReceive('sysGetTempDir')->once()->andReturn($tmpDir->url());
        $fs->shouldReceive('tempname')->once()->with($tmpDir->url())->andReturn($tmpFile->url());

        $result = $fs->createTempFile();

        $this->assertSame($tmpFile->url(), $result);
        $this->assertFileExists($result);
    }

    public function testCreateTempFileWhenTempnamFails(): void
    {
        $tmpDir = vfsStream::newDirectory('tmp')->at($this->root);

        $this->logger->shouldReceive('error')->once()->with('Could not create temporary file', [
            'tempDir' => $tmpDir->url(),
        ]);

        $fs = $this->getPartialLocalFilesystem();
        $fs->shouldReceive('sysGetTempDir')->once()->andReturn($tmpDir->url());
        $fs->shouldReceive('tempname')->once()->with($tmpDir->url())->andReturnFalse();

        $path = $fs->createTempFile();

        $this->assertFalse($path);
    }

    public function testCreateTempDir(): void
    {
        $tmpDir = vfsStream::newDirectory('tmp')->at($this->root);
        $uniqueId = 'woopie_random';
        $expectedPath = \sprintf('%s/%s', $tmpDir->url(), $uniqueId);

        $fs = $this->getPartialLocalFilesystem();
        $fs->shouldReceive('sysGetTempDir')->once()->andReturn($tmpDir->url());
        $fs->shouldReceive('uniqid')->once()->andReturn($uniqueId);
        $result = $fs->createTempDir();

        $this->assertSame($expectedPath, $result);
        $this->assertDirectoryExists($result);
    }

    public function testCreateTempDirWithSubdirs(): void
    {
        $tmpDir = vfsStream::newDirectory('tmp')->at($this->root);
        $mySubdir = '/my-subdir/another-one/';
        $uniqueId = 'woopie_random';
        $expectedPath = \sprintf('%s/%s/my-subdir/another-one', $tmpDir->url(), $uniqueId);

        $fs = $this->getPartialLocalFilesystem();
        $fs->shouldReceive('sysGetTempDir')->once()->andReturn($tmpDir->url());
        $fs->shouldReceive('uniqid')->once()->andReturn($uniqueId);
        $result = $fs->createTempDir($mySubdir);

        $this->assertSame($expectedPath, $result);
        $this->assertDirectoryExists($result);
    }

    public function testCreateTempDirWhenCreatingDirFails(): void
    {
        // Note the tempDir's permissions are set to 0000 (no permissions)
        $tmpDir = vfsStream::newDirectory('foobar')->chmod(0000)->at($this->root);

        $uniqueId = 'woopie_random';
        $expectedPath = \sprintf('%s/%s', $tmpDir->url(), $uniqueId);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Could not create temporary dir', [
                'tempDir' => $expectedPath,
            ]);

        $fs = $this->getPartialLocalFilesystem();
        $fs->shouldReceive('sysGetTempDir')->once()->andReturn($tmpDir->url());
        $fs->shouldReceive('uniqid')->once()->andReturn($uniqueId);
        $result = $fs->createTempDir();

        $this->assertFalse($result);
        $this->assertDirectoryDoesNotExist($expectedPath);
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
                'subdir' => [],
            ],
        ], $this->root);

        // Note the parentDir's permissions are set to 0500 (no permissions to delete inside the directory)
        $parentDir = $this->root->getChild('dir')->chmod(0500);
        $subDir = $this->root->getChild('dir/subdir');

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Could not delete directory', [
                'dirPath' => $subDir->url(),
            ]);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->deleteDirectory($parentDir->url());

        $this->assertFalse($result, 'The delete operation should return false');
        $this->assertDirectoryExists($parentDir->url());
        $this->assertDirectoryExists($subDir->url());
    }

    public function testDeleteFile(): void
    {
        vfsStream::newFile('file.txt')->at($this->root);

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
        $parentDir = vfsStream::newDirectory('parentDir', 0400)->at($this->root);
        $file = vfsStream::newFile('file.txt')->at($parentDir);

        $this->logger->shouldReceive('error')->once()->with('Could not delete local file', [
            'local_path' => $file->url(),
        ]);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->deleteFile($file->url());

        $this->assertFalse($result, 'The delete operation should return false');
        $this->assertTrue($parentDir->hasChild('file.txt'), 'The file should still exist');
    }

    public function testCreateStream(): void
    {
        $content = 'my content';
        $file = vfsStream::newFile('file.txt')->withContent($content)->at($this->root);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->createStream($file->url(), 'r');

        $this->assertIsResource($result);
        $this->assertSame('stream', get_resource_type($result));
        $this->assertSame($content, stream_get_contents($result));
    }

    public function testFailedCreateStream(): void
    {
        $nonExistingFile = $this->root->url() . '/file.txt';

        $this->logger->shouldReceive('error')->once()->with('Could not open local file file', [
            'local_path' => $nonExistingFile,
            'mode' => $mode = 'r',
        ]);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->createStream($nonExistingFile, $mode);

        $this->assertFalse($result);
    }

    public function testGetFileSize(): void
    {
        $file = vfsStream::newFile('file.txt')->withContent(LargeFileContent::withMegabytes(5))->at($this->root);

        $fs = new LocalFilesystem($this->logger);
        $result = $fs->getFileSize($file->url());

        $this->assertSame(5 * 1024 * 1024, $result);
    }

    public function testGetFileSizeLogsErrorOnFailure(): void
    {
        $nonExistingFile = $this->root->url() . '/file.txt';

        $fs = $this->getPartialLocalFilesystem();
        $fs->shouldReceive('filesize')->once()->andReturnFalse();

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Could not get file size', [
                'local_path' => $nonExistingFile,
            ]);

        $result = $fs->getFileSize($nonExistingFile);

        $this->assertFalse($result);
    }

    public function testGetIsSymlinkWhenNotSymlink(): void
    {
        $filename = 'file.txt';
        vfsStream::create([$filename => $this->getFaker()->sentence()], $this->root);

        $localFilesystem = new LocalFilesystem($this->logger);
        $this->assertFalse($localFilesystem->isSymlink(\sprintf('vfs://root/%s', $filename)));
    }

    public function testGetIsSymlinkWhenSymlink(): void
    {
        $filename = 'file.txt';
        vfsStream::create([$filename => $this->getFaker()->sentence()], $this->root);
        $path = \sprintf('vfs://root/%s', $filename);

        $linkname = $this->getFaker()->slug(1);
        \symlink($path, $linkname);

        $localFilesystem = new LocalFilesystem($this->logger);
        $this->assertTrue($localFilesystem->isSymlink($linkname));

        \unlink($linkname);
    }

    private function getPartialLocalFilesystem(): LocalFilesystem&MockInterface
    {
        return \Mockery::mock(LocalFilesystem::class, [$this->logger])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
