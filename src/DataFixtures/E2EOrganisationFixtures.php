<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Entity\Department;
use App\Entity\Organisation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * This is a set of fixtures for the Organisation entity. It is not meant to be used in production.
 */
class E2EOrganisationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const string REFERENCE = 'e2e-organisation-fixture-reference';

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['e2e'];
    }

    public function load(ObjectManager $manager): void
    {
        $documentPrefix1 = new DocumentPrefix();
        $documentPrefix1->setPrefix('E2E-A');

        $documentPrefix2 = new DocumentPrefix();
        $documentPrefix2->setPrefix('E2E-B');

        $entity = new Organisation();
        $entity->setName('E2E Test Organisation');
        $entity->addDocumentPrefix($documentPrefix1);
        $entity->addDocumentPrefix($documentPrefix2);
        $entity->addDepartment(
            $this->getReference(E2EDepartmentFixtures::REFERENCE_1, Department::class),
        );
        $entity->addDepartment(
            $this->getReference(E2EDepartmentFixtures::REFERENCE_2, Department::class),
        );

        $manager->persist($entity);
        $manager->flush();
        $this->addReference(self::REFERENCE, $entity);
    }

    /**
     * @return array<array-key, class-string>
     */
    public function getDependencies(): array
    {
        return [E2EDepartmentFixtures::class];
    }
}
