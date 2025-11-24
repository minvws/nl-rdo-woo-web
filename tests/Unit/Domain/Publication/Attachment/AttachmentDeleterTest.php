<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment;

use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\AttachmentDeleter;
use Shared\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Tests\Unit\UnitTestCase;

class AttachmentDeleterTest extends UnitTestCase
{
    private AttachmentDeleter $deleter;
    private AttachmentDeleteStrategyInterface&MockInterface $deleteStrategyA;
    private AttachmentDeleteStrategyInterface&MockInterface $deleteStrategyB;

    protected function setUp(): void
    {
        $this->deleteStrategyA = \Mockery::mock(AttachmentDeleteStrategyInterface::class);
        $this->deleteStrategyB = \Mockery::mock(AttachmentDeleteStrategyInterface::class);

        $this->deleter = new AttachmentDeleter(
            [$this->deleteStrategyA, $this->deleteStrategyB],
        );

        parent::setUp();
    }

    public function testAllStrategiesAreCalled(): void
    {
        $attachment = \Mockery::mock(AbstractAttachment::class);

        $this->deleteStrategyA->expects('delete')->with($attachment);
        $this->deleteStrategyB->expects('delete')->with($attachment);

        $this->deleter->delete($attachment);
    }
}
