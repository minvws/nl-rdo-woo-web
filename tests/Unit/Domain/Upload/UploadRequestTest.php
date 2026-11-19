<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Mockery;
use Shared\Domain\Upload\UploadRequest;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

class UploadRequestTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $request = new UploadRequest(
            $chunkIndex = 1,
            $chunkCount = 3,
            $uploadId = 'foo-bar-123',
            $uploadedFile = Mockery::mock(UploadedFile::class),
            $groupId = UploadGroupId::WOO_DECISION_DOCUMENTS,
            $params = new InputBag([
                'foo' => 'bar',
            ]),
        );

        self::assertEquals($chunkIndex, $request->chunkIndex);
        self::assertEquals($chunkCount, $request->chunkCount);
        self::assertEquals($uploadId, $request->uploadId);
        self::assertEquals($groupId, $request->groupId);
        self::assertTrue($request->isChunked());
        self::assertTrue($request->hasMoreChunksToFollow());

        $uploadedFile->expects('getClientOriginalName')->andReturn($name = 'foo.bar');
        self::assertEquals($name, $request->getFilename());

        $uploadedFile->expects('getClientMimeType')->andReturn($mimetype = 'foo/bar');
        self::assertEquals($mimetype, $request->getMimeType());

        self::assertEquals($params->all(), $request->additionalParameters->all());
    }

    public function testFromHttpRequestForGroup(): void
    {
        $chunkIndex = $this->getFaker()->numberBetween(0, 10);
        $chunkCount = $this->getFaker()->numberBetween(11, 20);
        $uploadId = $this->getFaker()->uuid();
        $departmentId = $this->getFaker()->uuid();
        $uploadedFile = Mockery::mock(UploadedFile::class);

        $httpRequest = new Request(
            request: [
                'chunkindex' => $chunkIndex,
                'totalchunkcount' => $chunkCount,
                'uuid' => $uploadId,
            ],
            files: [
                'file' => $uploadedFile,
            ],
        );

        $request = UploadRequest::fromHttpRequestForGroup(
            $httpRequest,
            UploadGroupId::DEPARTMENT,
            new InputBag(['departmentId' => $departmentId]),
        );

        self::assertSame($chunkIndex, $request->chunkIndex);
        self::assertSame($chunkCount, $request->chunkCount);
        self::assertSame($uploadId, $request->uploadId);
        self::assertSame($uploadedFile, $request->uploadedFile);
        self::assertSame(UploadGroupId::DEPARTMENT, $request->groupId);
        self::assertSame(['departmentId' => $departmentId], $request->additionalParameters->all());
    }
}
