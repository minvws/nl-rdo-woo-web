<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UploadEvents::VALIDATION, method: 'onValidate')]
readonly class FileTypeValidator
{
    public const ERROR_TECHNICAL = 'error.technical';
    public const ERROR_WHITELIST = 'error.whitelist';

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function onValidate(ValidationEvent $event): void
    {
        $groupParam = $event->getRequest()->request->getString('groupId');
        $groupId = UploadGroupId::tryFrom($groupParam);
        if (! $groupId) {
            $this->logger->error('Could not determine uploadgroup in filetype validator: ' . $groupParam);
            throw new ValidationException(self::ERROR_TECHNICAL);
        }

        if (! in_array($event->getFile()->getMimeType(), $groupId->getMimeTypes(), true)) {
            $this->logger->error(sprintf(
                'Mimetype %s not accepted for group %s',
                $event->getFile()->getMimeType(),
                $groupId->value,
            ));
            throw new ValidationException(self::ERROR_WHITELIST);
        }
    }
}
