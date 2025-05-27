<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Service\Encryption\EncryptionServiceInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\TextType;
use Psr\Log\LoggerInterface;

/**
 * This class is a Doctrine type that encrypts and decrypts data transparently. It will encrypt the string and stores it in the
 * database. When the data is retrieved from the database, the encrypted string is decrypted and returned.
 */
class EncryptedString extends TextType
{
    public const TYPE = 'encrypted_string';

    // Type classes are instantiated by Doctrine, so we can't inject the encryption service directly.
    // The encryption service is injected through the Kernel::boot() method.
    protected static EncryptionServiceInterface $encryptionService;
    protected static LoggerInterface $logger;

    public static function injectServices(
        EncryptionServiceInterface $encryptionService,
        LoggerInterface $logger,
    ): void {
        static::$encryptionService = $encryptionService;
        static::$logger = $logger;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return self::$encryptionService->encrypt(strval($value));
        } catch (\Throwable $e) {
            self::$logger->error('cannot convert to database value', [
                'exception' => $e->getMessage(),
            ]);

            throw SerializationFailed::new($value, self::TYPE, $e->getMessage(), $e);
        }
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        $value = parent::convertToPHPValue($value, $platform);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return self::$encryptionService->decrypt(strval($value));
        } catch (\Throwable $e) {
            self::$logger->error('cannot convert to php value', [
                'exception' => $e->getMessage(),
            ]);

            throw ValueNotConvertible::new($value, self::TYPE, previous: $e);
        }
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
