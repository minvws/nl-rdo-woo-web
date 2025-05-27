<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\WooIndex\Producer\Mapper\UrlMapper;
use App\Domain\WooIndex\Producer\Repository\RawUrlDto;
use App\Domain\WooIndex\Producer\Repository\UrlRepository;

final readonly class UrlProducer
{
    public function __construct(
        private UrlRepository $urlRepository,
        private UrlMapper $urlMapper,
    ) {
    }

    /**
     * @return \Generator<int,Url>
     */
    public function getAll(): \Generator
    {
        foreach ($this->fetchData() as $rawUrl) {
            yield $this->urlMapper->fromRawUrl($rawUrl);
        }
    }

    /**
     * @return \Generator<int,\Generator<int,Url>>
     */
    public function getChunked(int $chunkSize = 50_000): \Generator
    {
        yield from $this->doChunking($this->getAll(), $chunkSize);
    }

    /**
     * @template T
     *
     * @param \Generator<int,T> $producer
     *
     * @return \Generator<int,\Generator<int,T>>
     */
    private function doChunking(\Generator $producer, int $chunkSize): \Generator
    {
        while ($producer->valid()) {
            if (isset($chunkGen) && $chunkGen->valid()) {
                throw UnconsumedPreviousChunkGeneratorException::create();
            }

            yield $chunkGen = (function () use ($chunkSize, &$producer) {
                for ($i = 0; $i < $chunkSize && $producer->valid(); $i++) {
                    $signal = yield $producer->current();

                    if ($signal === ProducerSignal::STOP_CHUNK) {
                        break;
                    }

                    $producer->next();
                }
            })();
        }
    }

    /**
     * @return iterable<int,RawUrlDto>
     */
    private function fetchData(): iterable
    {
        yield from $this->urlRepository->getPublishedDocuments();
        yield from $this->urlRepository->getPublishedAttachments();
        yield from $this->urlRepository->getPublishedMainDocuments();
    }
}
