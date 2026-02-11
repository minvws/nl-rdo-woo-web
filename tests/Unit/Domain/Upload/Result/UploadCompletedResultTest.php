<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Result;

use Mockery;
use Shared\Domain\Upload\Result\UploadCompletedResult;
use Shared\Domain\Upload\UploadRequest;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;

class UploadCompletedResultTest extends UnitTestCase
{
    use MatchesSnapshots;

    public function testCreate(): void
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

        $result = UploadCompletedResult::create($request, $size);

        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
        self::assertEquals(['foo' => 'bar'], $result->additionalParameters->all());
    }
}
