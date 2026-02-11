<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Handler\S3;

use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Shared\Domain\Upload\Handler\S3\S3UploadHelper;
use Shared\Domain\Upload\UploadRequest;
use Shared\Service\Uploader\UploadGroupId;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;

use function sprintf;

class S3UploadHelperTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private S3UploadHelper $helper;
    private S3Client&MockInterface $s3Client;
    private string $bucket = 'some-bucket';
    private vfsStreamDirectory $vfs;

    protected function setUp(): void
    {
        $this->s3Client = Mockery::mock(S3Client::class);
        $this->helper = new S3UploadHelper($this->s3Client, $this->bucket);
        $this->vfs = vfsStream::setup();
    }

    public function testCreateMultipartUpload(): void
    {
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $request = new UploadRequest(
            chunkIndex: 0,
            chunkCount: 1,
            uploadId: $uploadId = 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $result = Mockery::mock(Result::class);
        $result->shouldReceive('hasKey')->with('UploadId')->andReturnTrue();
        $result->shouldReceive('get')->with('UploadId')->andReturn($id = 'test-123');

        $this->s3Client->expects('createMultipartUpload')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
        ])->andReturn($result);

        self::assertEquals($id, $this->helper->createMultipartUpload($request));
    }

    public function testUploadPart(): void
    {
        vfsStream::newFile($path = 'test-file.txt')
            ->withContent($content = 'foo')
            ->at($this->vfs);
        $realPath = sprintf('%s/%s', $this->vfs->url(), $path);

        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->shouldReceive('getRealPath')->andReturn($realPath);

        $request = new UploadRequest(
            chunkIndex: 0,
            chunkCount: 1,
            uploadId: $uploadId = 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $result = Mockery::mock(Result::class);
        $id = 'test-123';

        $this->s3Client->expects('uploadPart')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
            'PartNumber' => 1,
            'UploadId' => $id,
            'Body' => $content,
        ])->andReturn($result);

        $this->helper->uploadPart($request, $id);
    }

    public function testCompleteMultipartUpload(): void
    {
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $request = new UploadRequest(
            chunkIndex: 2,
            chunkCount: 3,
            uploadId: $uploadId = 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $id = 'test-123';

        $parts = ['a', 'b'];
        $result = new Result(['Parts' => $parts]);
        $this->s3Client->expects('listParts')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
            'UploadId' => $id,
        ])->andReturn($result);

        $this->s3Client->expects('completeMultipartUpload')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
            'UploadId' => $id,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
        ]);

        $headResult = Mockery::mock(Result::class);
        $headResult->shouldReceive('hasKey')->with('ContentLength')->andReturnTrue();
        $headResult->shouldReceive('get')->with('ContentLength')->andReturn($length = 456);
        $this->s3Client->expects('headObject')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
        ])->andReturn($headResult);

        self::assertEquals(
            $length,
            $this->helper->completeMultipartUpload($request, $id),
        );
    }

    public function testCopyUploadToPartWithSpacesInKeyPart(): void
    {
        $uploadId = 'test-123';
        $targetPath = 's3://other-bucket/documenten - 10.zip';

        $this->s3Client->expects('copyObject')->with([
            'Bucket' => 'other-bucket',
            'Key' => 'documenten - 10.zip',
            'CopySource' => 'some-bucket/test-123',
        ]);

        $this->helper->copyUploadToPath($uploadId, $targetPath);
    }

    public function testCopyUploadToPart(): void
    {
        $uploadId = 'test-123';
        $targetPath = 's3://other-bucket/foo-bar';

        $this->s3Client->expects('copyObject')->with([
            'Bucket' => 'other-bucket',
            'Key' => 'foo-bar',
            'CopySource' => 'some-bucket/test-123',
        ]);

        $this->helper->copyUploadToPath($uploadId, $targetPath);
    }

    public function testDeleteUpload(): void
    {
        $uploadId = 'test-123';

        $this->s3Client->expects('deleteObject')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
        ]);

        $this->helper->deleteUpload($uploadId);
    }

    public function testUploadFile(): void
    {
        $realPath = 'foo/bar.baz';
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->shouldReceive('getRealPath')->andReturn($realPath);

        $request = new UploadRequest(
            chunkIndex: 2,
            chunkCount: 3,
            uploadId: $uploadId = 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag(),
        );

        $this->s3Client->expects('putObject')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
            'SourceFile' => $realPath,
        ]);

        $this->helper->uploadFile($request);
    }

    public function testReadStreamWithoutLimit(): void
    {
        $uploadId = 'test-123';

        $stream = Mockery::mock(Stream::class);
        $result = Mockery::mock(Result::class);
        $result->shouldReceive('get')->with('Body')->andReturn($stream);

        $this->s3Client->expects('getObject')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
        ])->andReturn($result);

        $this->helper->readStream($uploadId, null);
    }

    public function testReadStreamWithLimit(): void
    {
        $uploadId = 'test-123';

        $stream = Mockery::mock(Stream::class);
        $result = Mockery::mock(Result::class);
        $result->shouldReceive('get')->with('Body')->andReturn($stream);

        $this->s3Client->expects('getObject')->with([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
            'Range' => 'bytes=0-1024',
        ])->andReturn($result);

        $this->helper->readStream($uploadId, 1024);
    }
}
