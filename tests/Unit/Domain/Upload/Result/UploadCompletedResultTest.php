<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Result;

use Mockery;
use Psr\Http\Message\StreamInterface;
use Shared\Domain\Upload\Result\UploadCompletedResult;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadRequest;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;

class UploadCompletedResultTest extends UnitTestCase
{
    public function testCreateFromUploadRequest(): void
    {
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $uploadedFile->expects('getClientOriginalName')->andReturn('foo.bar');
        $uploadedFile->expects('getClientMimeType')->andReturn('foo/bar');

        $request = new UploadRequest(
            chunkIndex: 1,
            chunkCount: 3,
            uploadId: 'foo-bar-123',
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            additionalParameters: new InputBag([
                'foo' => 'bar',
            ]),
        );

        $size = 123;

        $result = UploadCompletedResult::createFromUploadRequest($request, $size);

        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
        self::assertEquals(['foo' => 'bar'], $result->additionalParameters->all());
    }

    public function testCreateFromStreamUpload(): void
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->expects('getSize')->andReturn(123);

        $streamUpload = new StreamUpload(
            uploadId: 'foo-bar-123',
            fileName: 'foo.bar',
            groupId: UploadGroupId::WOO_DECISION_DOCUMENTS,
            stream: $stream,
            additionalParameters: new InputBag([
                'foo' => 'bar',
            ]),
        );

        $result = UploadCompletedResult::createFromStreamUpload($streamUpload);

        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
        self::assertEquals(['foo' => 'bar'], $result->additionalParameters->all());
    }
}
