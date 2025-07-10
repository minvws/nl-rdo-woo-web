<?php

declare(strict_types=1);

namespace App\Service\Stats\Handler;

use App\Service\Stats\WorkerStats;
use Doctrine\ORM\EntityManagerInterface;

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
