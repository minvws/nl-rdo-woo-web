<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex\Producer;

use App\Domain\WooIndex\Producer\ProducerSignal;
use App\Domain\WooIndex\Producer\UnconsumedPreviousChunkGeneratorException;
use App\Domain\WooIndex\Producer\Url;
use App\Domain\WooIndex\Producer\UrlProducer;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use App\Tests\Story\WooIndexCovenantStory;
use App\Tests\Story\WooIndexWooDecisionStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class UrlProducerTest extends KernelTestCase
{
    use IntegrationTestTrait;

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
        $urls = iterator_to_array($this->urlProducer->getAll());

        $this->assertCount(14, $urls);
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

        $this->assertSame(6, $numberOfChunks);
        $this->assertSame(18, $numberOfIterations);
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

        $this->assertSame(6, $numberOfChunks);
        $this->assertSame(11, $numberOfIterations);
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
