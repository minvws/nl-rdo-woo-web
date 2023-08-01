<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Service\Encryption\EncryptionServiceInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;

/**
 * This class is a Doctrine type that encrypts and decrypts data transparently. It will convert the given array as a json string and
 * encrypts this string. The encrypted string is stored in the database. When the data is retrieved from the database, the encrypted
 * string is decrypted and converted back to an array.
 */
class EncryptedArray extends Type
{
    // Type classes are instantiated by Doctrine, so we can't inject the encryption service directly.
    // The encryption service is injected through the Kernel::boot() method.
    protected static EncryptionServiceInterface $encryptionService;
    protected static LoggerInterface $logger;

    public static function injectServices(
        EncryptionServiceInterface $encryptionService,
        LoggerInterface $logger
    ): void {
        static::$encryptionService = $encryptionService;
        static::$logger = $logger;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'text';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            $value = json_encode($value, JSON_THROW_ON_ERROR);

            return self::$encryptionService->encrypt($value);
        } catch (\Throwable $e) {
            self::$logger->error('cannot convert to database value', [
                'exception' => $e->getMessage(),
            ]);

            throw ConversionException::conversionFailedSerialization($value, 'encrypted_array', $e->getMessage(), $e);
        }
    }

    /**
     * @return mixed|null
     *
     * @throws ConversionException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $value = self::$encryptionService->decrypt(strval($value));

            return json_decode(strval($value), true);
        } catch (\Throwable $e) {
            self::$logger->error('cannot convert to php value', [
                'exception' => $e->getMessage(),
            ]);

            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    public function getName(): string
    {
        return 'encrypted_array';
    }

    /**
     * We need to add the SQL comment hint, otherwise migrations not detect our doctrine type correctly.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
