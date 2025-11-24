<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\EntityWithFileInfo;

readonly class ElasticDocumentId
{
    public static function forDossier(AbstractDossier $dossier): string
    {
        return $dossier->getId()->toRfc4122();
    }

    public static function forEntityWithFileInfo(EntityWithFileInfo $entity): string
    {
        return $entity->getId()->toRfc4122();
    }

    public static function forObject(object $object): string
    {
        if ($object instanceof AbstractDossier) {
            return self::forDossier($object);
        }

        if ($object instanceof EntityWithFileInfo) {
            return self::forEntityWithFileInfo($object);
        }

        throw IndexException::cannotGenerateDocumentIdForObject($object);
    }
}
