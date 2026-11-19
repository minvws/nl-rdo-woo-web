<?php

declare(strict_types=1);

namespace Shared\Domain\Stream;

use GuzzleHttp\Psr7\HttpFactory;
use Override;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final readonly class StreamFactory implements StreamFactoryInterface
{
    public function __construct(private HttpFactory $streamFactory)
    {
    }

    #[Override]
    public function createStream(string $content = ''): StreamInterface
    {
        return $this->streamFactory->createStream($content);
    }

    #[Override]
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return $this->streamFactory->createStreamFromFile($filename, $mode);
    }

    #[Override]
    public function createStreamFromResource($resource): StreamInterface
    {
        return $this->streamFactory->createStreamFromResource($resource);
    }
}
