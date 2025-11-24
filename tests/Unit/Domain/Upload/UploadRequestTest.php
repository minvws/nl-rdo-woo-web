<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

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
            $uploadedFile = \Mockery::mock(UploadedFile::class),
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

        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn($name = 'foo.bar');
        self::assertEquals($name, $request->getFilename());

        $uploadedFile->shouldReceive('getClientMimeType')->andReturn($mimetype = 'foo/bar');
        self::assertEquals($mimetype, $request->getMimeType());

        self::assertEquals($params->all(), $request->additionalParameters->all());
    }

    public function testFromHttpRequest(): void
    {
        $httpRequest = new Request(
            query: [
                'foo' => 'bar',
                'chunkindex' => 2,
            ],
            request: [
                'chunkindex' => 2,
                'totalchunkcount' => 3,
                'uuid' => 'foo-bar-123',
                'groupId' => UploadGroupId::WOO_DECISION_DOCUMENTS->value,
            ],
            files: [
                'file' => $uploadedFile = \Mockery::mock(UploadedFile::class),
            ],
        );

        $request = UploadRequest::fromHttpRequest($httpRequest);

        self::assertEquals(2, $request->chunkIndex);
        self::assertEquals(3, $request->chunkCount);
        self::assertEquals('foo-bar-123', $request->uploadId);
        self::assertEquals(UploadGroupId::WOO_DECISION_DOCUMENTS, $request->groupId);
        self::assertTrue($request->isChunked());
        self::assertFalse($request->hasMoreChunksToFollow());

        $uploadedFile->shouldReceive('getClientOriginalName')->andReturn($name = 'foo.bar');
        self::assertEquals($name, $request->getFilename());

        $uploadedFile->shouldReceive('getClientMimeType')->andReturn($mimetype = 'foo/bar');
        self::assertEquals($mimetype, $request->getMimeType());

        self::assertEquals(['foo' => 'bar'], $request->additionalParameters->all());
    }

    public function testFromHttpRequestWithDossierId(): void
    {
        $httpRequest = new Request(
            query: [
                'foo' => 'bar',
                'chunkindex' => 2,
                'dossierId' => 'dossier-123', // gets overwritten by request payload value
            ],
            request: [
                'groupId' => UploadGroupId::WOO_DECISION_DOCUMENTS->value,
                'dossierId' => 'dossier-456',
            ],
            files: [
                'file' => \Mockery::mock(UploadedFile::class),
            ],
        );

        $request = UploadRequest::fromHttpRequest($httpRequest);

        self::assertEquals([
            'foo' => 'bar',
            'dossierId' => 'dossier-456',
        ], $request->additionalParameters->all());
    }

    public function testFromHttpRequestWithDepartmentId(): void
    {
        $httpRequest = new Request(
            query: [
                'foo' => 'bar',
                'chunkindex' => 2,
                'departmentId' => 'department-123', // gets overwritten by request payload value
            ],
            request: [
                'groupId' => UploadGroupId::DEPARTMENT->value,
                'departmentId' => 'department-456',
            ],
            files: [
                'file' => \Mockery::mock(UploadedFile::class),
            ],
        );

        $request = UploadRequest::fromHttpRequest($httpRequest);

        self::assertEquals([
            'foo' => 'bar',
            'departmentId' => 'department-456',
        ], $request->additionalParameters->all());
    }
}
