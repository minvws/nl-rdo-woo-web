<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Encryption;

use Mockery\MockInterface;
use ParagonIE\Halite\KeyFactory;
use Psr\Log\LoggerInterface;
use Shared\Service\Encryption\EncryptionService;
use Shared\Tests\Unit\UnitTestCase;

class EncryptionServiceTest extends UnitTestCase
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

        self::assertEquals($inputData, $decryptedData);
        self::assertNotEquals($inputData, $encryptedData);
    }
}
