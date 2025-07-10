<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Subject\Subject;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class E2ESubjectFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['e2e'];
    }

    public function load(ObjectManager $manager): void
    {
        $entity = new Subject();
        $entity->setName('E2E Test Subject');
        $entity->setOrganisation(
            $this->getReference(E2EOrganisationFixtures::REFERENCE, Organisation::class),
        );

        $manager->persist($entity);
        $manager->flush();
    }

    /**
     * @return array<array-key, class-string>
     */
    public function getDependencies(): array
    {
        return [E2EOrganisationFixtures::class];
    }
}
