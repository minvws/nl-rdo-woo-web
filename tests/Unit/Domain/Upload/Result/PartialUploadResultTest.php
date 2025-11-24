<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Result;

use Shared\Domain\Upload\Result\PartialUploadResult;
use Shared\Domain\Upload\UploadRequest;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;

class PartialUploadResultTest extends UnitTestCase
{
    use MatchesSnapshots;

    public function testCreate(): void
    {
        $uploadedFile = \Mockery::mock(UploadedFile::class);
        $uploadedFile->expects('getClientOriginalName')->andReturn('foo.bar');

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

        $result = PartialUploadResult::create($request);

        $this->assertMatchesSnapshot($result->toJsonResponse()->getContent());
    }
}
