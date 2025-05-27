<?php

declare(strict_types=1);

namespace App\Domain\Upload\Department;

use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadEvents::VALIDATION . '.department', method: 'onValidate', priority: 20)]
final readonly class DepartmentUploadSizeValidator
{
    public const MAX_FILE_SIZE_DEPARTMENT = 10 * 1024 * 1024; // 10 MB

    public const ERROR_MAX_SIZE_EXCEEDED = 'error.max_size_exceeded';

    public function onValidate(ValidationEvent $event): void
    {
        $groupId = $event->getRequest()->get('groupId');
        Assert::string($groupId);

        $groupId = UploadGroupId::tryFrom($groupId);
        if ($groupId === null) {
            return;
        }

        if (! $groupId->isDepartment()) {
            return;
        }

        $file = $event->getRequest()->files->get('file');
        Assert::isInstanceOf($file, UploadedFile::class);

        if ($file->getSize() > self::MAX_FILE_SIZE_DEPARTMENT) {
            throw new ValidationException(self::ERROR_MAX_SIZE_EXCEEDED);
        }
    }
}
