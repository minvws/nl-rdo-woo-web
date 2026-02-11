<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\TextType;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shared\Service\Encryption\EncryptionServiceInterface;
use Throwable;

use function strval;

/**
 * This class is a Doctrine type that encrypts and decrypts data transparently. It will encrypt the string and stores
 * it in the database. When the data is retrieved from the database, the encrypted string is decrypted and returned.
 */
class EncryptedString extends TextType
{
    public const TYPE = 'encrypted_string';

    /**
     * See Kernel.php boot() on how and what is injected.
     */
    public function __construct(private readonly ContainerInterface $locator)
    {
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return $this->getEncryptionService()->encrypt(strval($value));
        } catch (Throwable $e) {
            $this->getLogger()->error('cannot convert to database value', [
                'exception' => $e->getMessage(),
            ]);

            throw SerializationFailed::new($value, self::TYPE, $e->getMessage(), $e);
        }
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        $value = parent::convertToPHPValue($value, $platform);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return $this->getEncryptionService()->decrypt(strval($value));
        } catch (Throwable $e) {
            $this->getLogger()->error('cannot convert to php value', [
                'exception' => $e->getMessage(),
            ]);

            throw ValueNotConvertible::new($value, self::TYPE, previous: $e);
        }
    }

    private function getEncryptionService(): EncryptionServiceInterface
    {
        return $this->locator->get(EncryptionServiceInterface::class);
    }

    private function getLogger(): LoggerInterface
    {
        return $this->locator->get(LoggerInterface::class);
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
