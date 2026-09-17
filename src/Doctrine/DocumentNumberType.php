<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;
use Override;
use Shared\ValueObject\DocumentNumber;
use Webmozart\Assert\Assert;

final class DocumentNumberType extends TextType
{
    public const string NAME = 'document_number';

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): DocumentNumber
    {
        Assert::string($value);

        return DocumentNumber::fromString($value);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        Assert::isInstanceOf($value, DocumentNumber::class);

        return $value->toString();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
