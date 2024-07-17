<?php

declare(strict_types=1);

namespace App\Domain\Ingest\SubType;

use App\Domain\Ingest\IngestException;
use App\Domain\Ingest\IngestOptions;
use App\Entity\EntityWithFileInfo;

/**
 * This class is responsible for ingesting subtype entities (related to dossiers) into the system.
 */
readonly class SubTypeIngester
{
    /** @var SubTypeIngestStrategyInterface[] */
    private array $strategies;

    /**
     * @param iterable|SubTypeIngestStrategyInterface[] $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies instanceof \Traversable ? iterator_to_array($strategies) : $strategies;
    }

    public function ingest(EntityWithFileInfo $entity, IngestOptions $options): void
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($entity)) {
                $strategy->handle($entity, $options);

                return;
            }
        }

        throw IngestException::forNoMatchingSubTypeIngester($entity);
    }
}
