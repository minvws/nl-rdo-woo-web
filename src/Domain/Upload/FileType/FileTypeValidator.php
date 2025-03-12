<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

use App\Service\Uploader\UploadGroupId;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

        $mimeType = $event->getFile()->getMimeType();
        if ($this->isValidMimetype($mimeType, $groupId)) {
            return;
        }

        $uploadedFile = $event->getRequest()->files->get('file');
        if ($uploadedFile instanceof UploadedFile) {
            $detector = new FinfoMimeTypeDetector();
            $mimeType = $detector->detectMimeTypeFromPath($uploadedFile->getClientOriginalName());
            if ($this->isValidMimetype($mimeType, $groupId)) {
                return;
            }
        }

        $this->logger->error(sprintf(
            'Mimetype %s not accepted for group %s',
            $event->getFile()->getMimeType(),
            $groupId->value,
        ));

        throw new ValidationException(self::ERROR_WHITELIST);
    }

    private function isValidMimetype(?string $mimeType, UploadGroupId $groupId): bool
    {
        if ($mimeType === null) {
            return false;
        }

        return in_array(
            $mimeType,
            $groupId->getMimeTypes(),
            true,
        );
    }
}
