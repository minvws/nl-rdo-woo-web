<?php

declare(strict_types=1);

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\Subject;

class SubjectFixtures extends Fixture implements DependentFixtureInterface
{
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
