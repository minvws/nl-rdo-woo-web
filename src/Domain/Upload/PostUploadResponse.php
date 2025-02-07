<?php

declare(strict_types=1);

namespace App\Domain\Upload;

use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadEvents::POST_UPLOAD, method: 'onPostUpload')]
final readonly class PostUploadResponse
{
    public function __construct(private UploaderService $uploaderService)
    {
    }

    public function onPostUpload(PostUploadEvent $event): void
    {
        $uploaderGroupId = UploadGroupId::from($event->getRequest()->getPayload()->getString('groupId'));
        $uploadUuid = $event->getRequest()->getPayload()->getString('uuid');
        $pathname = $event->getFile()->getPathname();

        $this->uploaderService->registerUpload($uploadUuid, $pathname, $uploaderGroupId);

        $file = $event->getRequest()->files->get('file');
        Assert::isInstanceOf($file, UploadedFile::class);

        $fileData = [
            'uploadUuid' => $uploadUuid,
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $event->getFile()->getMimeType(),
            'size' => $event->getFile()->getSize(),
            'groupId' => $uploaderGroupId->value,
        ];

        $event->getResponse()['data'] = $fileData;
    }
}
