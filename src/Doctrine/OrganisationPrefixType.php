<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\ValueObject\OrganisationPrefix;
use Webmozart\Assert\Assert;

use function key_exists;
use function sprintf;

final class OrganisationPrefixType extends Type
{
    public const string NAME = 'organisation_prefix';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (! key_exists('length', $column)) {
            $column['length'] = OrganisationPrefix::MAX_LENGTH;
        }
        Assert::integer($column['length']);

        return sprintf('VARCHAR(%s)', $column['length']);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrganisationPrefix
    {
        if ($value === null) {
            return null;
        }

        Assert::string($value);

        return OrganisationPrefix::create($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        Assert::isInstanceOf($value, OrganisationPrefix::class);

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
