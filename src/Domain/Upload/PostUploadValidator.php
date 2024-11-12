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
    public function __construct(
        private readonly UploaderService $uploaderService,
    ) {
    }

    public function onPostUpload(PostUploadEvent $event): void
    {
        $groupId = $event->getRequest()->get('groupId');
        Assert::string($groupId);

        $groupId = UploadGroupId::from($groupId);

        $this->uploaderService->registerUpload($event, $groupId);

        $fileData = [
            'uploadUuid' => $event->getRequest()->get('uuid'),
            'originalName' => null,
            'mimeType' => $event->getFile()->getMimeType(),
            'size' => $event->getFile()->getSize(),
            'groupId' => $groupId->value,
        ];

        $file = $event->getRequest()->files->get('file');
        if ($file instanceof UploadedFile) {
            $fileData['originalName'] = $file->getClientOriginalName();
        }

        $event->getResponse()['data'] = $fileData;
    }
}
