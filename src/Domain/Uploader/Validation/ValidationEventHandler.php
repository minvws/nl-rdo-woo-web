<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Validation;

use App\Domain\Uploader\Command\ValidateUploadCommand;
use App\Domain\Uploader\Event\UploadCompletedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: UploadCompletedEvent::class, method: 'onUploadCompleted')]
final readonly class ValidationEventHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function onUploadCompleted(UploadCompletedEvent $event): void
    {
        $this->messageBus->dispatch(
            ValidateUploadCommand::forEntity($event->uploadEntity),
        );
    }
}
