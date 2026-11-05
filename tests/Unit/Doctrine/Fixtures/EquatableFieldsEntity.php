<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine\Fixtures;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Shared\Doctrine\DossierTitleType;
use Shared\Doctrine\PlainDateType;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;

final class EquatableFieldsEntity extends AbstractEquatableFieldsEntity
{
    public function __construct(
        public DossierTitle $title,
        public ?PlainDate $date = null,
        public string $name = '',
        public ?DateTimeImmutable $createdAt = null,
        public PlainDate|string|null $union = null,
    ) {
    }

    /**
     * @return ClassMetadata<self>
     */
    public static function metadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(self::class);
        $metadata->mapField(['fieldName' => 'title', 'type' => DossierTitleType::NAME]);
        $metadata->mapField(['fieldName' => 'date', 'type' => PlainDateType::NAME]);
        $metadata->mapField(['fieldName' => 'name', 'type' => Types::STRING]);
        $metadata->mapField(['fieldName' => 'createdAt', 'type' => Types::DATETIME_IMMUTABLE]);
        $metadata->mapField(['fieldName' => 'union', 'type' => Types::STRING]);
        $metadata->mapField([
            'fieldName' => 'inheritedTitle',
            'type' => DossierTitleType::NAME,
            'declared' => AbstractEquatableFieldsEntity::class,
        ]);
        $metadata->wakeupReflection(new RuntimeReflectionService());

        return $metadata;
    }
}
