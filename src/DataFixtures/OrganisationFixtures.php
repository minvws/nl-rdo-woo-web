<?php

declare(strict_types=1);

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\ValueObject\OrganisationPrefix;

/**
 * This is a set of fixtures for the Organisation entity. It is not meant to be used in production.
 */
class OrganisationFixtures extends Fixture implements DependentFixtureInterface
{
    public const string REFERENCE = 'organisation-fixture-reference';

    public function load(ObjectManager $manager): void
    {
        $entity = new Organisation();
        $entity->setName('Voorbeeld organisatie');
        $entity->setPrefix(OrganisationPrefix::create('PREFIX1'));
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
