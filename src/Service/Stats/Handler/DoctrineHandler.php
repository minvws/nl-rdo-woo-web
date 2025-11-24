<?php

declare(strict_types=1);

namespace Shared\Service\Stats\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Service\Stats\WorkerStats;

class DoctrineHandler implements StatsHandlerInterface
{
    public function __construct(protected EntityManagerInterface $doctrine)
    {
    }

    public function store(\DateTimeImmutable $dt, string $hostname, string $section, int $duration): void
    {
        $entity = new WorkerStats();
        $entity->setCreatedAt($dt);
        $entity->setHostname($hostname);
        $entity->setDuration($duration);
        $entity->setSection($section);

        $this->doctrine->persist($entity);
        $this->doctrine->flush();
    }
}
