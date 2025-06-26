<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Publication\Subject\Subject;
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
        return ['example'];
    }

    public function load(ObjectManager $manager): void
    {
        $entity = new Subject();
        $entity->setName('Voorbeeld onderwerp');
        $entity->setOrganisation(
            $this->getReference(OrganisationFixtures::REFERENCE, Organisation::class),
        );

        $manager->persist($entity);
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
