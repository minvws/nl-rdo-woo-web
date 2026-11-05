<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaService;
use Shared\Tests\Unit\UnitTestCase;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class TikaServiceTest extends UnitTestCase
{
    protected GuzzleClient&MockInterface $client;
    protected LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(GuzzleClient::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
    }

    public function testExtract(): void
    {
        $body = json_encode($contents = [
            'firstname' => 'John',
            'lastname' => 'Doe',
        ], JSON_THROW_ON_ERROR);

        $source = Utils::streamFor(Utils::tryFopen('php://memory', 'r'));

        $stream = Mockery::mock(StreamInterface::class);
        $stream
            ->expects('getContents')
            ->andReturn($body);

        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->expects('getBody')
            ->andReturn($stream);

        $this->client
            ->expects('put')
            ->with(
                '/tika/text',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/pdf',
                        'X-Tika-OCRmaxFileSizeToOcr' => '0',
                    ],
                    'body' => $source,
                ],
            )
            ->andReturn($response);

        $result = $this->getInstance()->extract($source);

        $this->assertSame($contents, $result);
    }

    public function testExtractWhenGuzzleThrowsAnException(): void
    {
        $source = Utils::streamFor(Utils::tryFopen('php://memory', 'r'));

        $request = Mockery::mock(RequestInterface::class);

        $this->client
            ->expects('put')
            ->with(
                '/tika/text',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/pdf',
                        'X-Tika-OCRmaxFileSizeToOcr' => '0',
                    ],
                    'body' => $source,
                ],
            )
            ->andThrows($exception = new ConnectException('My exception message', $request));

        $logContext = Mockery::mock(ContentExtractLogContext::class);

        $this->logger
            ->expects('error')
            ->with('Tika failed', [
                'exception' => $exception->getMessage(),
                'context' => $logContext,
            ]);

        $result = $this->getInstance()->extract(
            source: $source,
            logContext: $logContext,
        );

        $this->assertSame([], $result);
    }

    private function getInstance(): TikaService
    {
        return new TikaService($this->client, $this->logger);
    }
}
