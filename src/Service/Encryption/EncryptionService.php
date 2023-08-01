<?php

declare(strict_types=1);

namespace App\Service\Encryption;

use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;

/**
 * Encryption class for encrypting data at rest. Uses Halite.
 */
class EncryptionService implements EncryptionServiceInterface
{
    protected ?EncryptionKey $encryptionKey = null;
    protected LoggerInterface $logger;

    public function __construct(string $encryptionKey, LoggerInterface $logger)
    {
        $this->logger = $logger;

        if ($encryptionKey == '') {
            $this->logger->warning('Encryption key not set');

            $this->encryptionKey = null;

            return;
        }

        try {
            $this->encryptionKey = KeyFactory::importEncryptionKey(new HiddenString($encryptionKey));
        } catch (\Throwable $e) {
            $this->logger->error('Failed to import encryption key', [
                'exception' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to import encryption key', 0, $e);
        }
        $this->logger = $logger;
    }

    /**
     * Encrypts data with a symmetric key. Data is mac'd and salted.
     */
    public function encrypt(string $data): string
    {
        if ($this->encryptionKey === null) {
            $this->logger->error('Encryption key not set');

            throw new \RuntimeException('Encryption key not set');
        }

        try {
            return Crypto::encrypt(new HiddenString($data), $this->encryptionKey);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to encrypt data', [
                'exception' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to encrypt data', 0, $e);
        }
    }

    /**
     * Decrypts $data with the current symmetric key.
     */
    public function decrypt(string $data): string
    {
        if ($this->encryptionKey === null) {
            $this->logger->error('Encryption key not set');

            throw new \RuntimeException('Encryption key not set');
        }

        try {
            return Crypto::decrypt($data, $this->encryptionKey)->getString();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to decrypt data', [
                'exception' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to decrypt data', 0, $e);
        }
    }
}
