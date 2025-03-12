<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\DocumentUploadQueue;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Predis\ClientInterface;
use Symfony\Component\Uid\Uuid;

class DocumentUploadQueueTest extends UnitTestCase
{
    private DocumentUploadQueue $queue;
    private ClientInterface&MockInterface $redis;

    public function setUp(): void
    {
        $this->redis = \Mockery::mock(ClientInterface::class);

        $this->queue = new DocumentUploadQueue(
            $this->redis,
        );

        parent::setUp();
    }

    public function testAdd(): void
    {
        $uuid = '1ef401f7-958a-65c4-a92c-25f027c8b5e7';

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn(Uuid::fromRfc4122($uuid));

        $filename = 'foo.pdf';
        $this->redis->shouldReceive('lpush')->with('uploads:dossier:' . $uuid, [$filename]);

        $this->queue->add($wooDecision, $filename);
    }

    public function testRemove(): void
    {
        $uuid = '1ef401f7-958a-65c4-a92c-25f027c8b5e7';

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn(Uuid::fromRfc4122($uuid));

        $filename = 'foo.pdf';
        $this->redis->shouldReceive('lrem')->with('uploads:dossier:' . $uuid, 0, $filename);

        $this->queue->remove($wooDecision, $filename);
    }

    public function testClear(): void
    {
        $uuid = '1ef401f7-958a-65c4-a92c-25f027c8b5e7';

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn(Uuid::fromRfc4122($uuid));

        $this->redis->shouldReceive('del')->with('uploads:dossier:' . $uuid);

        $this->queue->clear($wooDecision);
    }

    public function testGetFilenames(): void
    {
        $uuid = '1ef401f7-958a-65c4-a92c-25f027c8b5e7';

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn(Uuid::fromRfc4122($uuid));

        $filenames = ['foo.pdf', 'bar.pdf'];
        $this->redis->shouldReceive('lrange')->with('uploads:dossier:' . $uuid, 0, -1)->andReturn($filenames);

        self::assertEquals(
            $filenames,
            $this->queue->getFilenames($wooDecision),
        );
    }
}
