<?php

declare(strict_types=1);

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\Subject;

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
        $subjectName = 'E2E Test Subject';

        $existingSubject = $manager->getRepository(Subject::class)->findOneBy(['name' => $subjectName]);
        if ($existingSubject) {
            return;
        }

        $entity = new Subject();
        $entity->setName($subjectName);
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
