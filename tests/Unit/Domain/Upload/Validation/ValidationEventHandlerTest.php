<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Validation;

use App\Domain\Upload\Command\ValidateUploadCommand;
use App\Domain\Upload\Event\UploadCompletedEvent;
use App\Domain\Upload\UploadEntity;
use App\Domain\Upload\Validation\ValidationEventHandler;
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
