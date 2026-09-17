<?php

declare(strict_types=1);

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\ValueObject\OrganisationPrefix;

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
        $department1 = $this->getReference(E2EDepartmentFixtures::REFERENCE_1, Department::class);
        $department2 = $this->getReference(E2EDepartmentFixtures::REFERENCE_2, Department::class);

        $organisation = $this->getOrCreateOrganisation(
            manager: $manager,
            name: 'E2E Test Organisation',
            organisationPrefix: OrganisationPrefix::create('E2E-A'),
            departments: [$department1, $department2],
        );
        $this->getOrCreateOrganisation(
            manager: $manager,
            name: 'Test Org 1',
            organisationPrefix: OrganisationPrefix::create('TESTORG1'),
            departments: [$department1],
        );

        $manager->flush();
        $this->addReference(self::REFERENCE, $organisation);
    }

    /**
     * @param list<Department> $departments
     */
    private function getOrCreateOrganisation(
        ObjectManager $manager,
        string $name,
        OrganisationPrefix $organisationPrefix,
        array $departments,
    ): Organisation {
        $organisation = $manager->getRepository(Organisation::class)->findOneBy(['name' => $name]);

        if (! $organisation instanceof Organisation) {
            $organisation = new Organisation();
            $organisation->setName($name);
            $organisation->setPrefix($organisationPrefix);
            $manager->persist($organisation);
        }

        // Restore links when a previous fixture load was interrupted.
        foreach ($departments as $department) {
            $organisation->addDepartment($department);
        }

        return $organisation;
    }

    /**
     * @return array<array-key, class-string>
     */
    public function getDependencies(): array
    {
        return [E2EDepartmentFixtures::class];
    }
}
