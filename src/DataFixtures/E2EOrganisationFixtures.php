<?php

declare(strict_types=1);

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;

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
        $organisationName = 'E2E Test Organisation';

        $department1 = $this->getReference(E2EDepartmentFixtures::REFERENCE_1, Department::class);
        $department2 = $this->getReference(E2EDepartmentFixtures::REFERENCE_2, Department::class);

        $existingOrg = $manager->getRepository(Organisation::class)->findOneBy(['name' => $organisationName]);
        if ($existingOrg) {
            // Self-heal partial state: an organisation left without its department links (e.g. by an
            // interrupted earlier load) renders an empty "bestuursorgaan" dropdown and blocks dossier
            // creation. addDepartment() is idempotent, so re-running restores the links without duplicates.
            $existingOrg->addDepartment($department1);
            $existingOrg->addDepartment($department2);
            $manager->flush();
            $this->addReference(self::REFERENCE, $existingOrg);

            return;
        }

        $documentPrefix1 = new DocumentPrefix('E2E-A');
        $documentPrefix2 = new DocumentPrefix('E2E-B');

        $entity = new Organisation();
        $entity->setName($organisationName);
        $entity->addDocumentPrefix($documentPrefix1);
        $entity->addDocumentPrefix($documentPrefix2);
        $entity->addDepartment($department1);
        $entity->addDepartment($department2);

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
