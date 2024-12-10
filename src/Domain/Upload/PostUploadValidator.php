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

#[AsEventListener(event: UploadEvents::POST_UPLOAD, method: 'onPostUpload', priority: 10)]
final readonly class PostUploadValidator
{
    public function __construct(private UploaderService $uploaderService)
    {
    }

    public function onPostUpload(PostUploadEvent $event): void
    {
        $uploaderGroupId = $event->getRequest()->get('groupId');
        Assert::string($uploaderGroupId);

        $uploaderGroupId = UploadGroupId::from($uploaderGroupId);

        $uploadUuid = $event->getRequest()->get('uuid');
        Assert::string($uploadUuid);

        $pathname = $event->getFile()->getPathname();
        Assert::string($pathname);

        $this->uploaderService->registerUpload($uploadUuid, $pathname, $uploaderGroupId);

        $fileData = [
            'uploadUuid' => $uploadUuid,
            'originalName' => null,
            'mimeType' => $event->getFile()->getMimeType(),
            'size' => $event->getFile()->getSize(),
            'groupId' => $uploaderGroupId->value,
        ];

        $file = $event->getRequest()->files->get('file');
        if ($file instanceof UploadedFile) {
            $fileData['originalName'] = $file->getClientOriginalName();
        }

        $event->getResponse()['data'] = $fileData;
    }
}
