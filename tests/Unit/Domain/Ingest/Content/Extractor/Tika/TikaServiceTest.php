<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content\Extractor\Tika;

use App\Domain\Ingest\Content\ContentExtractLogContext;
use App\Domain\Ingest\Content\Extractor\Tika\TikaService;
use App\Tests\Unit\UnitTestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

final class TikaServiceTest extends UnitTestCase
{
    protected vfsStreamDirectory $root;
    protected GuzzleClient&MockInterface $client;
    protected LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        $this->client = \Mockery::mock(GuzzleClient::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
    }

    public function testExtract(): void
    {
        $body = json_encode($contents = [
            'firstname' => 'John',
            'lastname' => 'Doe',
        ], JSON_THROW_ON_ERROR);

        vfsStream::newFile($sourceFile = 'sourcePath.txt')
            ->withContent($body)
            ->at($this->root);

        $sourcePath = sprintf('%s/%s', $this->root->url(), $sourceFile);

        $stream = \Mockery::mock(StreamInterface::class);
        $stream
            ->shouldReceive('getContents')
            ->andReturn($body);

        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('getBody')
            ->andReturn($stream);

        $this->client
            ->shouldReceive('put')
            ->once()
            ->with(
                '/tika/text',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/pdf',
                        'X-Tika-OCRmaxFileSizeToOcr' => 0,
                    ],
                    'body' => $body,
                ]
            )
            ->andReturn($response);

        $result = $this->getInstance()->extract($sourcePath);

        $this->assertSame($contents, $result);
    }

    public function testExtractWhenGuzzleThrowsAnException(): void
    {
        $body = json_encode([
            'firstname' => 'John',
            'lastname' => 'Doe',
        ], JSON_THROW_ON_ERROR);

        vfsStream::newFile($sourceFile = 'sourcePath.txt')
            ->withContent($body)
            ->at($this->root);

        $sourcePath = sprintf('%s/%s', $this->root->url(), $sourceFile);

        $request = \Mockery::mock(RequestInterface::class);

        $this->client
            ->shouldReceive('put')
            ->once()
            ->with(
                '/tika/text',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/pdf',
                        'X-Tika-OCRmaxFileSizeToOcr' => 0,
                    ],
                    'body' => $body,
                ],
            )
            ->andThrows($exception = new ConnectException('My exception message', $request));

        $logContext = \Mockery::mock(ContentExtractLogContext::class);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Tika failed', [
                'sourcePath' => $sourcePath,
                'exception' => $exception->getMessage(),
                'context' => $logContext,
            ]);

        $result = $this->getInstance()->extract(
            sourcePath: $sourcePath,
            logContext: $logContext,
        );

        $this->assertSame([], $result);
    }

    private function getInstance(): TikaService
    {
        return new TikaService($this->client, $this->logger);
    }
}
