<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\WooIndex\Producer;

use Shared\Domain\WooIndex\Producer\ProducerSignal;
use Shared\Domain\WooIndex\Producer\UnconsumedPreviousChunkGeneratorException;
use Shared\Domain\WooIndex\Producer\Url;
use Shared\Domain\WooIndex\Producer\UrlProducer;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexAnnualReportStory;
use Shared\Tests\Story\WooIndexCovenantStory;
use Shared\Tests\Story\WooIndexWooDecisionStory;
use Zenstruck\Foundry\Attribute\WithStory;

final class UrlProducerTest extends SharedWebTestCase
{
    private UrlProducer $urlProducer;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->urlProducer = self::getContainer()->get(UrlProducer::class);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testGetUrls(): void
    {
        $urls = iterator_to_array($this->urlProducer->getAll(), false);

        $this->assertCount(32, $urls);
        foreach ($urls as $url) {
            $this->assertInstanceOf(Url::class, $url);
        }
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    #[WithStory(WooIndexAnnualReportStory::class)]
    #[WithStory(WooIndexCovenantStory::class)]
    public function testGetChunked(): void
    {
        $numberOfChunks = 0;
        $numberOfIterations = 0;

        foreach ($this->urlProducer->getChunked(3) as $chunk) {
            $numberOfChunks++;
            foreach ($chunk as $url) {
                $numberOfIterations++;
            }
        }

        $this->assertSame(36, $numberOfIterations, 'We should have 36 iterations');
        $this->assertSame(12, $numberOfChunks, 'We should have 12 chunks');
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testGetChunkedWithStoppingChunk(): void
    {
        $numberOfChunks = 0;
        $numberOfIterations = 0;

        foreach ($this->urlProducer->getChunked(2) as $chunk) {
            $numberOfChunks++;
            foreach ($chunk as $url) {
                $numberOfIterations++;

                // Stop the chunk after 5 urls. In real business logic this could be for any reason
                // like time limit, memory limit, storage size, etc.:
                if ($numberOfIterations === 5) {
                    break;
                }
            }

            // If the generator was not consumed fully, we will stop the current generator so it's items will move to
            // the next chunk. If you don't do this it will throw an UnconsumedPreviousChunkGeneratorException:
            if ($chunk->valid()) {
                $chunk->send(ProducerSignal::STOP_CHUNK);
                continue;
            }
        }

        $this->assertSame(29, $numberOfIterations, 'We should have 23 iterations, because we stopped the chunk after 5 urls');
        $this->assertSame(15, $numberOfChunks, 'We should have 15 chunks, because we stopped the chunk after 5 urls');
    }

    /**
     * @return array<string,array{chunkSize:int}>
     */
    public static function getInvalidChunkSizeData(): array
    {
        return [
            'chunk size 0' => [
                'chunkSize' => 0,
            ],
            'chunk size 50_001' => [
                'chunkSize' => 50_001,
            ],
        ];
    }

    #[WithStory(WooIndexCovenantStory::class)]
    public function testGetChunkedThrowsExceptionOnUnconsumedPreviousGenerator(): void
    {
        $this->expectExceptionObject(UnconsumedPreviousChunkGeneratorException::create());

        foreach ($this->urlProducer->getChunked(2) as $chunk) {
            // We are not consuming $chunk an purpose so on the second iteration it should throw an exception
        }
    }
}
