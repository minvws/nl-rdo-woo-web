<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use Symfony\Component\Uid\Uuid;

class IngestException extends \RuntimeException
{
    public static function forCannotFindDossier(Uuid $id): self
    {
        return new self(sprintf(
            'Cannot find dossier with UUID %s',
            $id->toRfc4122(),
        ));
    }

    public static function forNoMatchingSubTypeIngester(object $entity): self
    {
        return new self(sprintf(
            'No matching subtype ingester found for entity of class %s',
            $entity::class,
        ));
    }
}
