<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\TextType;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Shared\Service\Encryption\EncryptionServiceInterface;

/**
 * This class is a Doctrine type that encrypts and decrypts data transparently. It will convert the given array as a
 * json string and encrypts this string. The encrypted string is stored in the database. When the data is retrieved from
 * the database, the encrypted string is decrypted and converted back to an array.
 */
class EncryptedArray extends TextType
{
    public const TYPE = 'encrypted_array';

    /**
     * See Kernel.php boot() on how and what is injected.
     */
    public function __construct(private readonly ContainerInterface $locator)
    {
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
            $value = json_encode($value, JSON_THROW_ON_ERROR);

            return $this->getEncryptionService()->encrypt($value);
        } catch (\Throwable $e) {
            $this->getLogger()->error('cannot convert to database value', [
                'exception' => $e->getMessage(),
            ]);

            throw SerializationFailed::new($value, self::TYPE, $e->getMessage(), $e);
        }
    }

    /**
     * @return mixed|null
     *
     * @throws ConversionException
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        $value = parent::convertToPHPValue($value, $platform);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            $value = $this->getEncryptionService()->decrypt(strval($value));

            return json_decode(strval($value), true);
        } catch (\Throwable $e) {
            $this->getLogger()->error('cannot convert to php value', [
                'exception' => $e->getMessage(),
            ]);

            throw ValueNotConvertible::new($value, 'encrypted_array', previous: $e);
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
