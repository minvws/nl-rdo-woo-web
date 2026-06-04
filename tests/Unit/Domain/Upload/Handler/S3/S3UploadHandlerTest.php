<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Handler\S3;

use GuzzleHttp\Psr7\Stream;
use League\Flysystem\FilesystemOperator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Override;
use Psr\Http\Message\StreamInterface;
use Shared\Domain\Upload\Handler\S3\S3UploadHandler;
use Shared\Domain\Upload\Handler\S3\S3UploadHelper;
use Shared\Domain\Upload\Result\PartialUploadResult;
use Shared\Domain\Upload\Result\UploadCompletedResult;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadRequest;
use Shared\Service\Uploader\UploadGroupId;
use Shared\ValueObject\ExternalId;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Webmozart\Assert\Assert;

use function fclose;
use function fopen;
use function sprintf;

class S3UploadHandlerTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private S3UploadHandler $handler;
    private S3UploadHelper&MockInterface $s3uploadHelper;
    private vfsStreamDirectory $vfs;

    #[Override]
    protected function setUp(): void
    {
        $this->s3uploadHelper = Mockery::mock(S3UploadHelper::class);

        $this->handler = new S3UploadHandler(
            $this->s3uploadHelper,
        );

        $this->vfs = vfsStream::setup();
    }

    public function testHandleSinglePartUploadRequest(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);

        vfsStream::newFile($path = 'test-file.txt')
            ->withContent('foo')
            ->at($this->vfs);

        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->expects('getRealPath')->andReturn(sprintf('%s/%s', $this->vfs->url(), $path));
        $uploadedFile->expects('getClientOriginalName')->andReturn('foo.bar');
        $uploadedFile->expects('getClientMimeType')->andReturn('foo/bar');

        $request = new UploadRequest(
            chunkIndex: 0,
            chunkCount: 1,
            uploadId: 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $this->s3uploadHelper->expects('uploadFile')->with($request);

        $result = $this->handler->handleUpload($uploadEntity, $request);

        self::assertInstanceOf(UploadCompletedResult::class, $result);
        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }

    public function testHandleStreamUpload(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);

        $stream = Mockery::mock(StreamInterface::class);
        $stream->expects('getSize')->andReturn(123);

        $streamUpload = new StreamUpload(
            fileName: 'foobar.pdf',
            stream: $stream,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: [],
            uploadId: 'foo-bar-123',
        );

        $this->s3uploadHelper->expects('uploadStream')->with($streamUpload);

        $result = $this->handler->handleStreamUpload($uploadEntity, $streamUpload);

        self::assertInstanceOf(UploadCompletedResult::class, $result);
        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }

    public function testHandleFirstChunkOfMultiPartUploadRequest(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);

        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->expects('getClientOriginalName')->andReturn('foo.bar');

        $request = new UploadRequest(
            chunkIndex: 0,
            chunkCount: 3,
            uploadId: 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $this->s3uploadHelper
            ->expects('createMultipartUpload')
            ->with($request)
            ->andReturn($externalId = 'foo-789');

        $uploadEntity->expects('setExternalId')->with($externalId);

        $this->s3uploadHelper
            ->expects('uploadPart')
            ->with($request, $externalId);

        $result = $this->handler->handleUpload($uploadEntity, $request);

        self::assertInstanceOf(PartialUploadResult::class, $result);
        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }

    public function testHandleLastChunkOfMultiPartUploadRequest(): void
    {
        $externalId = 'foo-789';

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getExternalId')->andReturn(ExternalId::create($externalId));

        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->expects('getClientOriginalName')->andReturn('foo.bar');
        $uploadedFile->expects('getClientMimeType')->andReturn('foo/bar');

        $request = new UploadRequest(
            chunkIndex: 2,
            chunkCount: 3,
            uploadId: 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $this->s3uploadHelper
            ->expects('uploadPart')
            ->with($request, $externalId);

        $this->s3uploadHelper
            ->expects('completeMultipartUpload')
            ->with($request, $externalId)
            ->andReturn(345);

        $result = $this->handler->handleUpload($uploadEntity, $request);

        self::assertInstanceOf(UploadCompletedResult::class, $result);
        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }

    public function testMoveUploadedFileToStorage(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadId')->andReturn($uploadId = 'foo-789');

        $path = 'foo/bar.baz';

        $filesystem = Mockery::mock(FilesystemOperator::class);
        $filesystem->expects('publicUrl')->with($path)->andReturn($targetPath = 's3://x/y/z');

        $this->s3uploadHelper->expects('copyUploadToPath')->with($uploadId, $targetPath);

        $this->handler->moveUploadedFileToStorage($uploadEntity, $filesystem, $path);
    }

    public function testDeleteUploadedFile(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadId')->andReturn($uploadId = 'foo-789');

        $this->s3uploadHelper->expects('deleteUpload')->with($uploadId);

        $this->handler->deleteUploadedFile($uploadEntity);
    }

    public function testCopyUploadedFileToFilesystem(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadId')->andReturn($uploadId = 'foo-789');

        $limit = 789;
        $path = 'foo/bar.baz';
        $writeStream = fopen('php://temp', 'r+');
        Assert::notFalse($writeStream);
        $stream = Mockery::mock(Stream::class);
        $stream->expects('detach')->andReturn($writeStream);

        $this->s3uploadHelper->expects('readStream')->with($uploadId, $limit)->andReturn($stream);

        $filesystem = Mockery::mock(FilesystemOperator::class);
        $filesystem->expects('writeStream')->with($path, $writeStream);

        $this->handler->copyUploadedFileToFilesystem($uploadEntity, $limit, $filesystem, $path);

        fclose($writeStream);
    }
}
