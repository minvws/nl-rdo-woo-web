<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Webmozart\Assert\Assert;

use function key_exists;
use function sprintf;

final class LandingPageSlugType extends Type
{
    public const string NAME = 'landing_page_slug';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (! key_exists('length', $column)) {
            $column['length'] = LandingPageSlug::MAX_LENGTH;
        }

        Assert::integer($column['length']);

        return sprintf('VARCHAR(%s)', $column['length']);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?LandingPageSlug
    {
        if ($value === null) {
            return null;
        }

        Assert::string($value);

        return LandingPageSlug::create($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        Assert::isInstanceOf($value, LandingPageSlug::class);

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
