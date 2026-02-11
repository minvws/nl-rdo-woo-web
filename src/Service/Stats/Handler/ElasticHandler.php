<?php

declare(strict_types=1);

namespace Shared\Service\Stats\Handler;

use DateTimeImmutable;
use DateTimeInterface;
use Shared\Service\Elastic\ElasticClientInterface;
use Symfony\Component\Uid\Uuid;

class ElasticHandler implements StatsHandlerInterface
{
    protected const string INDEX = 'worker_stats';

    public function __construct(
        private readonly ElasticClientInterface $elasticClient,
    ) {
    }

    public function store(DateTimeImmutable $dt, string $hostname, string $section, int $duration): void
    {
        $this->elasticClient->create([
            'index' => self::INDEX,
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
