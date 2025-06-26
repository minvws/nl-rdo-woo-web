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
class OrganisationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const string REFERENCE = 'organisation-fixture-reference';

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['example'];
    }

    public function load(ObjectManager $manager): void
    {
        $documentPrefix1 = new DocumentPrefix();
        $documentPrefix1->setPrefix('PREFIX1');

        $documentPrefix2 = new DocumentPrefix();
        $documentPrefix2->setPrefix('PREFIX2');

        $entity = new Organisation();
        $entity->setName('Voorbeeld organisatie');
        $entity->addDocumentPrefix($documentPrefix1);
        $entity->addDocumentPrefix($documentPrefix2);
        $entity->addDepartment(
            $this->getReference(DepartmentFixtures::REFERENCE_1, Department::class),
        );
        $entity->addDepartment(
            $this->getReference(DepartmentFixtures::REFERENCE_2, Department::class),
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
        return [DepartmentFixtures::class];
    }
}
