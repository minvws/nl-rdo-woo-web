<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\AttachmentDeleter;
use App\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AttachmentDeleterTest extends MockeryTestCase
{
    private AttachmentDeleter $deleter;
    private AttachmentDeleteStrategyInterface&MockInterface $deleteStrategyA;
    private AttachmentDeleteStrategyInterface&MockInterface $deleteStrategyB;

    public function setUp(): void
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
