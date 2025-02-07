<?php

declare(strict_types=1);

namespace App\Domain\Upload\AntiVirus;

use App\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use App\Domain\Upload\UploadedFile;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// Intentionally not enabled for the 'test' environment
#[When(env: 'dev')]
#[When(env: 'prod')]
#[AsEventListener(event: UploadEvents::VALIDATION, method: 'onValidate')]
readonly class ClamAvUploadValidator
{
    public const ERROR_TECHNICAL = 'error.technical';
    public const ERROR_UNSAFE = 'error.unsafe';

    public function __construct(
        private ClamAvFileScanner $scanner,
        private SevenZipFileStrategy $sevenZipFileStrategy,
    ) {
    }

    public function onValidate(ValidationEvent $event): void
    {
        $result = $this->scanner->scan(
            $event->getFile()->getPathname(),
        );

        if ($result->isMaxSizeExceeded()) {
            // Zip archives may ignore the max size limitation for clamAv.
            // In that case no scanning is done on the archive, only individual files within the archive are scanned.
            if ($this->sevenZipFileStrategy->canProcess(UploadedFile::fromFile($event->getFile()))) {
                return;
            }

            throw new ValidationException(self::ERROR_TECHNICAL);
        }

        if ($result->isTechnicalError()) {
            throw new ValidationException(self::ERROR_TECHNICAL);
        }

        if ($result->isNotSafe()) {
            throw new ValidationException(self::ERROR_UNSAFE);
        }
    }
}
