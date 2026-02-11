<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index;

use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\DossierType;

use function sprintf;

class IndexException extends RuntimeException
{
    public static function forUnsupportedDossierType(DossierType $dossierType): self
    {
        return new self(sprintf(
            'Cannot index dossier of type %s',
            $dossierType->value,
        ));
    }

    public static function forUnsupportedSubType(object $entity): self
    {
        return new self(sprintf(
            'Cannot index subtype of class %s',
            $entity::class,
        ));
    }

    public static function noTypeFoundForEntityClass(string $entityClass): self
    {
        return new self(sprintf(
            'No ES document type defined for entity of class %s',
            $entityClass,
        ));
    }

    public static function cannotGenerateDocumentIdForObject(object $object): self
    {
        return new self(sprintf(
            'Cannot determine Elastic document id for object of class %s',
            $object::class,
        ));
    }
}
