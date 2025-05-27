<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Uploader\Validation;

use App\Domain\Uploader\Command\ValidateUploadCommand;
use App\Domain\Uploader\Event\UploadCompletedEvent;
use App\Domain\Uploader\UploadEntity;
use App\Domain\Uploader\Validation\ValidationEventHandler;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ValidationEventHandlerTest extends MockeryTestCase
{
    public function testOnUploadCompleted(): void
    {
        $messageBus = \Mockery::mock(MessageBusInterface::class);

        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getId')->andReturn($uuid = Uuid::v6());

        $event = new UploadCompletedEvent($uploadEntity);

        $messageBus->expects('dispatch')->with(\Mockery::on(
            static function (ValidateUploadCommand $command) use ($uuid) {
                self::assertEquals($uuid, $command->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $handler = new ValidationEventHandler($messageBus);
        $handler->onUploadCompleted($event);
    }
}
