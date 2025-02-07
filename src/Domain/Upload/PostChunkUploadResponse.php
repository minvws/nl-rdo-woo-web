<?php

declare(strict_types=1);

namespace App\Domain\Upload;

use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\PostChunkUploadEvent;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadEvents::POST_CHUNK_UPLOAD, method: 'onPostChunkUpload', priority: 10)]
final readonly class PostChunkUploadResponse
{
    public function onPostChunkUpload(PostChunkUploadEvent $event): void
    {
        $uploaderGroupId = UploadGroupId::from($event->getRequest()->getPayload()->getString('groupId'));
        $uploadUuid = $event->getRequest()->getPayload()->getString('uuid');

        $file = $event->getRequest()->files->get('file');
        Assert::isInstanceOf($file, UploadedFile::class);

        $fileData = [
            'uploadUuid' => $uploadUuid,
            'originalName' => $file->getClientOriginalName(),
            'groupId' => $uploaderGroupId->value,
        ];

        $event->getResponse()['data'] = $fileData;
    }
}
