<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UploaderService $uploaderService,
    ) {
    }

    public function postUploadEvent(PostUploadEvent $event): void
    {
        $groupId = $this->getGroupId($event);

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

    public static function getSubscribedEvents(): array
    {
        return [
            UploadEvents::POST_UPLOAD => 'postUploadEvent',
        ];
    }

    private function getGroupId(PostUploadEvent $event): UploadGroupId
    {
        $groupId = $event->getRequest()->get('groupId');

        return is_string($groupId)
            ? UploadGroupId::tryFrom($groupId) ?? UploadGroupId::DEFAULT
            : UploadGroupId::DEFAULT;
    }
}
