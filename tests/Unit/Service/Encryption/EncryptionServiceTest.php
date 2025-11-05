<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Encryption;

use App\Service\Encryption\EncryptionService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use ParagonIE\Halite\KeyFactory;
use Psr\Log\LoggerInterface;

class EncryptionServiceTest extends MockeryTestCase
{
    private string $encryptionKey;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        $encKey = KeyFactory::generateEncryptionKey();
        $this->encryptionKey = KeyFactory::export($encKey)->getString();

        $this->logger = \Mockery::mock(LoggerInterface::class);

        parent::setUp();
    }

    public function testEncryptThrowsExceptionForMissingEncryptionKey(): void
    {
        $this->logger->shouldReceive('warning')->once();
        $encryptionService = new EncryptionService('', $this->logger);

        $this->expectException(\RuntimeException::class);
        $this->logger->shouldReceive('error')->once();
        $encryptionService->encrypt('some data');
    }

    public function testDecryptThrowsExceptionForMissingEncryptionKey(): void
    {
        $this->logger->shouldReceive('warning')->once();
        $encryptionService = new EncryptionService('', $this->logger);

        $this->expectException(\RuntimeException::class);
        $this->logger->shouldReceive('error')->once();
        $encryptionService->decrypt('some data');
    }

    public function testEncryptAndDecryptedCycleReturnsTheOriginalInput(): void
    {
        $encryptionService = new EncryptionService(
            $this->encryptionKey,
            $this->logger
        );

        $inputData = 'some VERY secret data!';
        $encryptedData = $encryptionService->encrypt($inputData);
        $decryptedData = $encryptionService->decrypt($encryptedData);

        self::assertEquals($decryptedData, $inputData);
        self::assertNotEquals($encryptedData, $inputData);
    }
}
