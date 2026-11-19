<?php

declare(strict_types=1);

namespace Unit\Service\Storage;

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Utils;
use RuntimeException;
use Shared\Service\Storage\FileHashService;
use Shared\Tests\Unit\UnitTestCase;

use function fopen;
use function hash;

use const DIRECTORY_SEPARATOR;

class FileHashServiceTest extends UnitTestCase
{
    public function testCalculate(): void
    {
        $hash = FileHashService::calculate(__DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt');

        self::assertEquals('b1b113c6ed8ab3a14779f7c54179eac2b87d39fcebbf65a50556b8d68caaa2fb', $hash);
    }

    public function testCalculateWhenFileNotExists(): void
    {
        $this->expectException(RuntimeException::class);

        FileHashService::calculate('non-existing-file');
    }

    public function testCalculateResourceHash(): void
    {
        $stream = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'dummy.txt', 'r');
        if ($stream === false) {
            $this->fail('cannot open dummy file');
        }

        $hash = FileHashService::calculateResourceHash($stream);

        self::assertEquals('b1b113c6ed8ab3a14779f7c54179eac2b87d39fcebbf65a50556b8d68caaa2fb', $hash);
    }

    public function testCalculatePsrStreamHash(): void
    {
        $testContent = 'This is a test string to calculate the hash of.';

        $stream = Utils::streamFor($testContent);
        $hash = FileHashService::calculatePsrStreamHash($stream);

        $this->assertMatchesTextSnapshot($hash);
    }

    public function testCalculatePsrStreamHashForNonSeekableStream(): void
    {
        $testContent = 'This is a test string to calculate the hash of.';

        $stream = new NoSeekStream(Utils::streamFor($testContent));

        self::assertSame(hash('sha256', $testContent), FileHashService::calculatePsrStreamHash($stream));
    }
}
