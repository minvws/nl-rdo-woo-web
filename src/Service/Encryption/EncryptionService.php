<?php

declare(strict_types=1);

namespace Shared\Service\Encryption;

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

    public function __construct(string $encryptionKey, protected LoggerInterface $logger)
    {
        if ($encryptionKey === '') {
            $this->logger->warning('Encryption key input empty');
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
    }

    /**
     * Encrypts data with a symmetric key. Data is mac'd and salted.
     */
    public function encrypt(string $data): string
    {
        $encryptionKey = $this->getEncryptionKey();

        try {
            return Crypto::encrypt(new HiddenString($data), $encryptionKey);
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
        $encryptionKey = $this->getEncryptionKey();

        try {
            return Crypto::decrypt($data, $encryptionKey)->getString();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to decrypt data', [
                'exception' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to decrypt data', 0, $e);
        }
    }

    private function getEncryptionKey(): EncryptionKey
    {
        if ($this->encryptionKey === null) {
            $this->logger->error('Encryption key not set');

            throw new \RuntimeException('Encryption key not set');
        }

        return $this->encryptionKey;
    }
}
