<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shared\Doctrine\DossierTitleType;
use Shared\Doctrine\EquatableFields;
use Shared\Tests\Unit\Doctrine\Fixtures\EquatableFieldsEntity;
use Shared\Tests\Unit\UnitTestCase;

final class EquatableFieldsTest extends UnitTestCase
{
    public function testOfReturnsOnlyFieldsTypedAsEquatable(): void
    {
        self::assertSame(
            ['title', 'date', 'inheritedTitle'],
            EquatableFields::of(EquatableFieldsEntity::metadata()),
        );
    }

    public function testOfSkipsFieldsInlinedFromAnEmbeddable(): void
    {
        $metadata = EquatableFieldsEntity::metadata();
        $metadata->mapField([
            'fieldName' => 'embedded.title',
            'type' => DossierTitleType::NAME,
            'declaredField' => 'embedded',
            'originalField' => 'title',
            'originalClass' => EquatableFieldsEntity::class,
        ]);

        self::assertSame(['title', 'date', 'inheritedTitle'], EquatableFields::of($metadata));
    }

    public function testOfReturnsAnEmptyListWithoutEquatableFields(): void
    {
        $metadata = new ClassMetadata(EquatableFieldsEntity::class);
        $metadata->mapField(['fieldName' => 'name', 'type' => Types::STRING]);
        $metadata->mapField(['fieldName' => 'createdAt', 'type' => Types::DATETIME_IMMUTABLE]);

        self::assertSame([], EquatableFields::of($metadata));
    }
}
