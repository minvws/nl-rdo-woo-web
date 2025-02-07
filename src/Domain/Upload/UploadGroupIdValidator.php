<?php

declare(strict_types=1);

namespace App\Domain\Upload;

use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadEvents::VALIDATION, method: 'onValidate', priority: 10)]
final readonly class UploadGroupIdValidator
{
    public function onValidate(ValidationEvent $event): void
    {
        $groupId = $event->getRequest()->get('groupId');

        Assert::string($groupId);

        if (UploadGroupId::tryFrom($groupId) === null) {
            throw new ValidationException('Invalid groupId provided');
        }
    }
}
