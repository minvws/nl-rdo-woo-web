<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\ValueObject\DossierTitle;
use Webmozart\Assert\Assert;

use function key_exists;
use function sprintf;

final class DossierTitleType extends Type
{
    public const string NAME = 'dossier_title';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (! key_exists('length', $column)) {
            $column['length'] = 500;
        }
        Assert::integer($column['length']);

        return sprintf('VARCHAR(%s)', $column['length']);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): DossierTitle
    {
        Assert::String($value);

        return DossierTitle::create($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        Assert::isInstanceOf($value, DossierTitle::class);

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
