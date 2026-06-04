<?php

declare(strict_types=1);

namespace Unit\Service\Storage;

use GuzzleHttp\Psr7\Utils;
use RuntimeException;
use Shared\Service\Storage\FileHashService;
use Shared\Tests\Unit\UnitTestCase;

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

    public function testCalculatePsrStreamHash(): void
    {
        $testContent = 'This is a test string to calculate the hash of.';

        $stream = Utils::streamFor($testContent);
        $hash = FileHashService::calculatePsrStreamHash($stream);

        $this->assertMatchesTextSnapshot($hash);
    }
}
