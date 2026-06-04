<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\ValueObject\PlainDate;
use Webmozart\Assert\Assert;

final class PlainDateType extends Type
{
    public const string NAME = 'plain_date';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'DATE';
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?PlainDate
    {
        Assert::nullOrString($value);
        if ($value === null) {
            return null;
        }

        return PlainDate::createFromFormat(PlainDate::DEFAULT_STRING_FORMAT, $value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        Assert::nullOrIsInstanceOf($value, PlainDate::class);
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
