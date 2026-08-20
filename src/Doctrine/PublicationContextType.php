<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\ValueObject\PublicationContext;
use Webmozart\Assert\Assert;

use function key_exists;
use function sprintf;

final class PublicationContextType extends Type
{
    public const string NAME = 'publication_context';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (! key_exists('length', $column)) {
            $column['length'] = PublicationContext::MAX_LENGTH;
        }
        Assert::integer($column['length']);

        return sprintf('VARCHAR(%s)', $column['length']);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?PublicationContext
    {
        Assert::nullOrString($value);
        if ($value === null) {
            return null;
        }

        return PublicationContext::fromString($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        Assert::nullOrIsInstanceOf($value, PublicationContext::class);
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
