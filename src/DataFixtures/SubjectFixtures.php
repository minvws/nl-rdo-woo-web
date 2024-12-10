<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Theme\Covid19Subject;
use App\Entity\Organisation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubjectFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['subject'];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Organisation $organisation */
        $organisation = $manager->getRepository(Organisation::class)->findOneBy(['name' => 'Programmadirectie Openbaarheid']);

        foreach (Covid19Subject::values() as $value) {
            $entity = new Subject();
            $entity->setName($value);
            $entity->setOrganisation($organisation);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @return array<array-key, class-string>
     */
    public function getDependencies(): array
    {
        return [OrganisationFixtures::class];
    }
}
