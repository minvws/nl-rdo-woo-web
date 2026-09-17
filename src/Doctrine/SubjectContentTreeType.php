<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Override;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Webmozart\Assert\Assert;

final class SubjectContentTreeType extends JsonType
{
    public const string NAME = 'subject_content_tree';

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?SubjectContentTree
    {
        $decoded = parent::convertToPHPValue($value, $platform);

        if ($decoded === null) {
            return null;
        }

        Assert::isArray($decoded);

        return SubjectContentTree::fromArray($decoded);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        Assert::isInstanceOf($value, SubjectContentTree::class);

        return parent::convertToDatabaseValue($value->toArray(), $platform);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
