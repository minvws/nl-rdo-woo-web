<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\Collection;

final class HasAttachmentsTest extends UnitTestCase
{
    public function testGetAttachments(): void
    {
        $collection = \Mockery::mock(Collection::class);
        $entity = $this->getEntityWithAttachments($collection);

        $this->assertSame($collection, $entity->getAttachments());
    }

    public function testAddAttachment(): void
    {
        $attachment = \Mockery::mock(AbstractAttachment::class);
        $collection = \Mockery::mock(Collection::class);
        $collection->shouldReceive('contains')->once()->with($attachment)->andReturn(false);
        $collection->shouldReceive('add')->once()->with($attachment);

        $entity = $this->getEntityWithAttachments($collection);

        $result = $entity->addAttachment($attachment);

        $this->assertSame($entity, $result);
    }

    public function testAddAttachmentWithAttachmentAlreadyExisting(): void
    {
        $attachment = \Mockery::mock(AbstractAttachment::class);
        $collection = \Mockery::mock(Collection::class);
        $collection->shouldReceive('contains')->once()->with($attachment)->andReturn(true);
        $collection->shouldNotReceive('add');

        $entity = $this->getEntityWithAttachments($collection);

        $result = $entity->addAttachment($attachment);

        $this->assertSame($entity, $result);
    }

    public function testRemoveAttachment(): void
    {
        $attachment = \Mockery::mock(AbstractAttachment::class);
        $collection = \Mockery::mock(Collection::class);
        $collection->shouldReceive('removeElement')->once()->with($attachment);

        $entity = $this->getEntityWithAttachments($collection);

        $result = $entity->removeAttachment($attachment);

        $this->assertSame($entity, $result);
    }

    private function getEntityWithAttachments(Collection $attachments): EntityWithAttachments
    {
        $entity = new class() implements EntityWithAttachments {
            use HasAttachments;

            public Collection $attachments;

            public function getAttachmentEntityClass(): string
            {
                return CovenantAttachment::class;
            }

            public function getAttachmentTransition(): DossierStatusTransition
            {
                return DossierStatusTransition::UPDATE_CONTENT;
            }
        };
        $entity->attachments = $attachments;

        return $entity;
    }
}
