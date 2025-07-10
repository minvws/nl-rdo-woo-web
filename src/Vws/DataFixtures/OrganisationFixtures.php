<?php

declare(strict_types=1);

namespace App\Vws\DataFixtures;

use App\Domain\Department\Department;
use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Dossier\DocumentPrefix;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * This is a set of fixtures for the Organisation entity. It is not meant to be used in production.
 */
class OrganisationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const string REFERENCE = 'vws-organisation-fixture-reference';

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['vws'];
    }

    public function load(ObjectManager $manager): void
    {
        $documentPrefix1 = new DocumentPrefix();
        $documentPrefix1->setPrefix('VWS');

        $entity = new Organisation();
        $entity->setName('Directie Open Overheid');
        $entity->addDocumentPrefix($documentPrefix1);
        $entity->addDepartment(
            $this->getReference(DepartmentFixtures::REFERENCE, Department::class),
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
