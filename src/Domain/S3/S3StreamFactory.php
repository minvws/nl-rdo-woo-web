<?php

declare(strict_types=1);

namespace App\Domain\S3;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Webmozart\Assert\Assert;

readonly class S3StreamFactory implements StreamFactory
{
    public function createReadOnlyStream(string $bucketName, string $key): StreamInterface
    {
        $fileName = $this->getFileName($bucketName, $key);

        return Utils::streamFor($this->doFopen($fileName, StreamMode::READ_ONLY));
    }

    public function createWriteOnlyStream(string $bucketName, string $key): StreamInterface
    {
        $fileName = $this->getFileName($bucketName, $key);

        return Utils::streamFor($this->doFopen($fileName, StreamMode::WRITE_ONLY));
    }

    /**
     * @codeCoverageIgnore
     *
     * @return resource
     */
    protected function doFopen(string $filename, StreamMode $mode)
    {
        $resource = fopen($filename, $mode->value);

        Assert::resource($resource, message: sprintf('Failed to open stream for "%s"', $filename));

        return $resource;
    }

    private function getFileName(string $bucketName, string $key): string
    {
        return sprintf('s3://%s/%s', $bucketName, $key);
    }
}
