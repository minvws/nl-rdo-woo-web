<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\WorkerStats;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class WorkerStatsFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('nl_NL');
        $date = $faker->dateTimeThisYear();

        for ($i = 0; $i != 1000; $i++) {
            $interval = random_int(0, 10000);
            $date->modify("+ {$interval} milliseconds");

            $entity = new WorkerStats();
            $entity->setCreatedAt(\DateTimeImmutable::createFromMutable($date));
            $entity->setDuration(random_int(100, 100000));
            $entity->setHostname(strval($faker->randomElement(['worker1', 'worker2'])));
            $entity->setSection(strval($faker->randomElement(['tika', 'tesseract', 'pdftk', 'split'])));
            $manager->persist($entity);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['stats'];
    }
}
