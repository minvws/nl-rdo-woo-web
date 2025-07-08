<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Result;

use App\Domain\Upload\Result\UploadCompletedResult;
use App\Domain\Upload\UploadRequest;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;

class UploadCompletedResultTest extends MockeryTestCase
{
    use MatchesSnapshots;

    public function testCreate(): void
    {
        $uploadedFile = \Mockery::mock(UploadedFile::class);
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
