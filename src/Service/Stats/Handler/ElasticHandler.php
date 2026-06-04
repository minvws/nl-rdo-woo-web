<?php

declare(strict_types=1);

namespace Shared\Service\Stats\Handler;

use DateTimeImmutable;
use DateTimeInterface;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Service\Elastic\ElasticClientInterface;
use Symfony\Component\Uid\Uuid;

class ElasticHandler implements StatsHandlerInterface
{
    public function __construct(
        private readonly ElasticClientInterface $elasticClient,
        private readonly ElasticConfig $elasticConfig,
    ) {
    }

    public function store(DateTimeImmutable $dt, string $hostname, string $section, int $duration): void
    {
        $this->elasticClient->create([
            'index' => $this->elasticConfig->workerStatsIndex,
            'id' => Uuid::v4(),
            'body' => [
                'created_at' => $dt->format(DateTimeInterface::ATOM),
                'hostname' => $hostname,
                'section' => $section,
                'duration' => $duration,
            ],
        ]);
    }
}
