<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Stats;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Shared\Service\Stats\Handler\DoctrineHandler;
use Shared\Service\Stats\WorkerStats;
use Shared\Tests\Unit\UnitTestCase;

final class DoctrineHandlerTest extends UnitTestCase
{
    public function testStore(): void
    {
        $dateTime = new DateTimeImmutable();
        $hostname = $this->getFaker()->word();
        $section = $this->getFaker()->word();
        $duration = $this->getFaker()->randomDigit();

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('persist')
            ->with(Mockery::on(static function (WorkerStats $workerStats) use ($dateTime, $hostname, $section, $duration): bool {
                return $workerStats->getCreatedAt() === $dateTime
                    && $workerStats->getHostname() === $hostname
                    && $workerStats->getSection() == $section
                    && $workerStats->getDuration() === $duration;
            }));
        $entityManager->expects('flush');

        $doctrineHandler = new DoctrineHandler($entityManager);
        $doctrineHandler->store($dateTime, $hostname, $section, $duration);
    }
}
