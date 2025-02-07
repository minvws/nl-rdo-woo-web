<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\SubType;

use App\Domain\Ingest\Process\IngestProcessException;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Publication\EntityWithFileInfo;

/**
 * This class is responsible for ingesting subtype entities (related to dossiers) into the system.
 */
readonly class SubTypeIngester
{
    /**
     * @param iterable<array-key,SubTypeIngestStrategyInterface> $strategies
     */
    public function __construct(private iterable $strategies)
    {
    }

    public function ingest(EntityWithFileInfo $entity, IngestProcessOptions $options): void
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($entity)) {
                $strategy->handle($entity, $options);

                return;
            }
        }

        throw IngestProcessException::forNoMatchingSubTypeIngester($entity);
    }
}
