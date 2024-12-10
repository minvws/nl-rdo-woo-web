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
final readonly class PostChunkUploadValidator
{
    public function onPostChunkUpload(PostChunkUploadEvent $event): void
    {
        $uploaderGroupId = $event->getRequest()->get('groupId');
        Assert::string($uploaderGroupId);

        $groupId = UploadGroupId::from($uploaderGroupId);

        $uploadUuid = $event->getRequest()->get('uuid');
        Assert::string($uploadUuid);

        $fileData = [
            'uploadUuid' => $uploadUuid,
            'originalName' => null,
            'groupId' => $groupId->value,
        ];

        $file = $event->getRequest()->files->get('file');
        if ($file instanceof UploadedFile) {
            $fileData['originalName'] = $file->getClientOriginalName();
        }

        $event->getResponse()['data'] = $fileData;
    }
}
