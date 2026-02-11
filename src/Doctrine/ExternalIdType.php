<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function key_exists;
use function sprintf;

final class ExternalIdType extends Type
{
    public const string NAME = 'external_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (! key_exists('length', $column)) {
            $column['length'] = 128;
        }
        Assert::integer($column['length']);

        return sprintf('VARCHAR(%s)', $column['length']);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ExternalId
    {
        Assert::nullOrString($value);
        if ($value === null) {
            return null;
        }

        return ExternalId::create($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        Assert::nullOrIsInstanceOf($value, ExternalId::class);
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
