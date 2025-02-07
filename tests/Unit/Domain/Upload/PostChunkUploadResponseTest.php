<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload;

use App\Domain\Upload\PostChunkUploadResponse;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\PostChunkUploadEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

final class PostChunkUploadResponseTest extends UnitTestCase
{
    public function testOnPostChunkUpload(): void
    {
        /** @var MockInterface&UploadedFile $file */
        $file = \Mockery::mock(UploadedFile::class);
        $file
            ->shouldReceive('getClientOriginalName')
            ->once()
            ->andReturn($originalName = 'originalName');

        /** @var MockInterface&FileBag $fileBag */
        $fileBag = \Mockery::mock(FileBag::class);
        $fileBag
            ->shouldReceive('get')
            ->with('file')
            ->andReturn($file);

        $inputBag = new InputBag([
            'groupId' => $groupId = UploadGroupId::ATTACHMENTS->value,
            'uuid' => $uuid = 'uuid',
        ]);

        /** @var MockInterface&Request $request */
        $request = \Mockery::mock(Request::class);
        $request->files = $fileBag;
        $request->shouldReceive('getPayload')->andReturn($inputBag);

        /** @var MockInterface&ResponseInterface $response */
        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('offsetSet')
            ->once()
            ->with('data', [
                'uploadUuid' => $uuid,
                'originalName' => $originalName,
                'groupId' => $groupId,
            ]);

        $event = new PostChunkUploadEvent(
            chunk: 'chunk',
            response: $response,
            request: $request,
            isLast: false,
            type: 'general',
            config: [],
        );

        (new PostChunkUploadResponse())->onPostChunkUpload($event);
    }
}
