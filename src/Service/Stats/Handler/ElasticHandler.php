<?php

declare(strict_types=1);

namespace App\Service\Stats\Handler;

use App\Service\Elastic\ElasticClientInterface;
use Symfony\Component\Uid\Uuid;

class ElasticHandler implements StatsHandlerInterface
{
    protected ElasticClientInterface $elastic;

    protected const INDEX = 'worker_stats';

    public function __construct(ElasticClientInterface $elastic)
    {
        $this->elastic = $elastic;
    }

    public function store(\DateTimeImmutable $dt, string $hostname, string $section, int $duration): void
    {
        $this->elastic->create([
            'index' => self::INDEX,
            'id' => Uuid::v4(),
            'body' => [
                'created_at' => $dt->format(\DateTimeInterface::ATOM),
                'hostname' => $hostname,
                'section' => $section,
                'duration' => $duration,
            ],
        ]);
    }
}
