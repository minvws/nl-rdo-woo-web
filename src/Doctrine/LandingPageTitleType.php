<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Webmozart\Assert\Assert;

use function key_exists;
use function sprintf;

final class LandingPageTitleType extends Type
{
    public const string NAME = 'landing_page_title';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (! key_exists('length', $column)) {
            $column['length'] = LandingPageTitle::MAX_LENGTH;
        }

        Assert::integer($column['length']);

        return sprintf('VARCHAR(%s)', $column['length']);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?LandingPageTitle
    {
        if ($value === null) {
            return null;
        }

        Assert::string($value);

        return LandingPageTitle::create($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        Assert::isInstanceOf($value, LandingPageTitle::class);

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
