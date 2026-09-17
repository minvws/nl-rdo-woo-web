<?php

declare(strict_types=1);

namespace WooMinVWS\DataFixtures;

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
    public const string REFERENCE = 'vws-organisation-fixture-reference';

    public function load(ObjectManager $manager): void
    {
        $entity = new Organisation();
        $entity->setName('Directie Open Overheid');
        $entity->setPrefix(OrganisationPrefix::create('MINVWS'));
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
