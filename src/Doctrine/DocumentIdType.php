<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use Shared\ValueObject\DocumentId;
use Webmozart\Assert\Assert;

use function key_exists;
use function sprintf;

final class DocumentIdType extends Type
{
    public const string NAME = 'document_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (! key_exists('length', $column)) {
            $column['length'] = DocumentId::MAX_LENGTH;
        }
        Assert::integer($column['length']);

        return sprintf('VARCHAR(%s)', $column['length']);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?DocumentId
    {
        Assert::nullOrString($value);
        if ($value === null) {
            return null;
        }

        return DocumentId::create($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        Assert::nullOrIsInstanceOf($value, DocumentId::class);
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
