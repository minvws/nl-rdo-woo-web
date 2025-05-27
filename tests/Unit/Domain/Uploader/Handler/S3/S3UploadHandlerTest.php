<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Uploader\Handler\S3;

use App\Domain\Uploader\Handler\S3\S3UploadHandler;
use App\Domain\Uploader\Handler\S3\S3UploadHelper;
use App\Domain\Uploader\Result\PartialUploadResult;
use App\Domain\Uploader\Result\UploadCompletedResult;
use App\Domain\Uploader\UploadEntity;
use App\Domain\Uploader\UploadRequest;
use App\Service\Uploader\UploadGroupId;
use GuzzleHttp\Psr7\Stream;
use League\Flysystem\FilesystemOperator;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Webmozart\Assert\Assert;

class S3UploadHandlerTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private S3UploadHandler $handler;
    private S3UploadHelper&MockInterface $s3uploadHelper;
    private vfsStreamDirectory $vfs;

    public function setUp(): void
    {
        $this->s3uploadHelper = \Mockery::mock(S3UploadHelper::class);

        $this->handler = new S3UploadHandler(
            $this->s3uploadHelper,
        );

        $this->vfs = vfsStream::setup();
    }

    public function testHandleSinglePartUploadRequest(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);

        vfsStream::newFile($path = 'test-file.txt')
            ->withContent('foo')
            ->at($this->vfs);

        $uploadedFile = \Mockery::mock(UploadedFile::class);
        $uploadedFile->shouldReceive('getRealPath')->andReturn(sprintf('%s/%s', $this->vfs->url(), $path));
        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn('foo.bar');
        $uploadedFile->shouldReceive('getClientMimeType')->andReturn('foo/bar');

        $request = new UploadRequest(
            chunkIndex: 0,
            chunkCount: 1,
            uploadId: 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $this->s3uploadHelper->shouldReceive('uploadFile')->with($request);

        $result = $this->handler->handleUploadRequest($uploadEntity, $request);

        self::assertInstanceOf(UploadCompletedResult::class, $result);
        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }

    public function testHandleFirstChunkOfMultiPartUploadRequest(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);

        $uploadedFile = \Mockery::mock(UploadedFile::class);
        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn('foo.bar');
        $uploadedFile->shouldReceive('getClientMimeType')->andReturn('foo/bar');

        $request = new UploadRequest(
            chunkIndex: 0,
            chunkCount: 3,
            uploadId: 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $this->s3uploadHelper
            ->shouldReceive('createMultipartUpload')
            ->with($request)
            ->andReturn($externalId = 'foo-789');

        $uploadEntity->expects('setExternalId')->with($externalId);

        $this->s3uploadHelper
            ->shouldReceive('uploadPart')
            ->with($request, $externalId);

        $result = $this->handler->handleUploadRequest($uploadEntity, $request);

        self::assertInstanceOf(PartialUploadResult::class, $result);
        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }

    public function testHandleLastChunkOfMultiPartUploadRequest(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getExternalId')->andReturn($externalId = 'foo-789');

        $uploadedFile = \Mockery::mock(UploadedFile::class);
        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn('foo.bar');
        $uploadedFile->shouldReceive('getClientMimeType')->andReturn('foo/bar');

        $request = new UploadRequest(
            chunkIndex: 2,
            chunkCount: 3,
            uploadId: 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $this->s3uploadHelper
            ->shouldReceive('uploadPart')
            ->with($request, $externalId);

        $this->s3uploadHelper
            ->shouldReceive('completeMultipartUpload')
            ->with($request, $externalId)
            ->andReturn(345);

        $result = $this->handler->handleUploadRequest($uploadEntity, $request);

        self::assertInstanceOf(UploadCompletedResult::class, $result);
        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }

    public function testMoveUploadedFileToStorage(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId = 'foo-789');

        $path = 'foo/bar.baz';

        $filesystem = \Mockery::mock(FilesystemOperator::class);
        $filesystem->expects('publicUrl')->with($path)->andReturn($targetPath = 's3://x/y/z');

        $this->s3uploadHelper->expects('copyUploadToPath')->with($uploadId, $targetPath);

        $this->handler->moveUploadedFileToStorage($uploadEntity, $filesystem, $path);
    }

    public function testDeleteUploadedFile(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId = 'foo-789');

        $this->s3uploadHelper->expects('deleteUpload')->with($uploadId);

        $this->handler->deleteUploadedFile($uploadEntity);
    }

    public function testCopyUploadedFileToFilesystem(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadId')->andReturn($uploadId = 'foo-789');

        $limit = 789;
        $path = 'foo/bar.baz';
        $writeStream = fopen('php://temp', 'r+');
        Assert::notFalse($writeStream);
        $stream = \Mockery::mock(Stream::class);
        $stream->expects('detach')->andReturn($writeStream);

        $this->s3uploadHelper->expects('readStream')->with($uploadId, $limit)->andReturn($stream);

        $filesystem = \Mockery::mock(FilesystemOperator::class);
        $filesystem->expects('writeStream')->with($path, $writeStream);

        $this->handler->copyUploadedFileToFilesystem($uploadEntity, $limit, $filesystem, $path);

        fclose($writeStream);
    }
}
