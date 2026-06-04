<?php

declare(strict_types=1);

namespace Shared\Service\Storage;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Webmozart\Assert\Assert;

use function hash_file;
use function hash_final;
use function hash_init;
use function hash_update;
use function is_readable;
use function sprintf;

class FileHashService
{
    public const string HASH_ALGORITHM = 'sha256';
    private const int BUFFER_SIZE = 8192;

    public static function calculate(string $path): string
    {
        if (! is_readable($path)) {
            throw new RuntimeException('Cannot read file for hash generation: ' . $path);
        }

        $hash = hash_file(self::HASH_ALGORITHM, $path);

        if ($hash === false) {
            throw new RuntimeException(sprintf('Cannot generate hash for file: %s', $path));
        }

        return $hash;
    }

    public static function calculatePsrStreamHash(StreamInterface $stream): string
    {
        Assert::true($stream->isReadable(), 'Stream must be readable to calculate hash.');
        Assert::true($stream->isSeekable(), 'Stream must be seekable to calculate hash.');

        $pos = $stream->tell();
        $stream->rewind();

        $hashContext = hash_init(self::HASH_ALGORITHM);

        while (! $stream->eof()) {
            $data = $stream->read(self::BUFFER_SIZE);
            if ($data === '') {
                break;
            }
            hash_update($hashContext, $data);
        }

        $hash = hash_final($hashContext);

        $stream->seek($pos);

        return $hash;
    }
}
