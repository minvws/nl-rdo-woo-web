<?php

declare(strict_types=1);

namespace WooMinVWS\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\Subject;
use WooMinVWS\Search\Theme\Covid19Subject;

class Covid19SubjectFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organisation = $this->getReference(OrganisationFixtures::REFERENCE, Organisation::class);

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
