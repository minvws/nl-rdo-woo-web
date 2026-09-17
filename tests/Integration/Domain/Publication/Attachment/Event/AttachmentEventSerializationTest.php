<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Attachment\Event;

use Shared\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class AttachmentEventSerializationTest extends SharedWebTestCase
{
    public function testAttachmentUpdatedEventSurvivesTransportSerialization(): void
    {
        $serializer = self::getContainer()->get('messenger.transport.symfony_serializer');
        self::assertInstanceOf(SerializerInterface::class, $serializer);

        $event = new AttachmentUpdatedEvent(
            Uuid::v6(),
            Uuid::v6(),
            'foo.pdf',
            'pdf',
            '1234',
            fileUpdated: false,
            metadataUpdated: true,
        );

        $decoded = $serializer->decode($serializer->encode(new Envelope($event)))->getMessage();

        self::assertInstanceOf(AttachmentUpdatedEvent::class, $decoded);
        self::assertTrue($event->attachmentId->equals($decoded->attachmentId));
        self::assertTrue($event->dossierId->equals($decoded->dossierId));
        self::assertSame('foo.pdf', $decoded->fileName);
        self::assertTrue($decoded->metadataUpdated);
    }
}
