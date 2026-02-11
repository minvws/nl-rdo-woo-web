<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Validation;

use Mockery;
use Shared\Domain\Upload\Command\ValidateUploadCommand;
use Shared\Domain\Upload\Event\UploadCompletedEvent;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\Validation\ValidationEventHandler;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ValidationEventHandlerTest extends UnitTestCase
{
    public function testOnUploadCompleted(): void
    {
        $messageBus = Mockery::mock(MessageBusInterface::class);

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getId')->andReturn($uuid = Uuid::v6());

        $event = new UploadCompletedEvent($uploadEntity);

        $messageBus->expects('dispatch')->with(Mockery::on(
            static function (ValidateUploadCommand $command) use ($uuid) {
                self::assertEquals($uuid, $command->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $handler = new ValidationEventHandler($messageBus);
        $handler->onUploadCompleted($event);
    }
}
