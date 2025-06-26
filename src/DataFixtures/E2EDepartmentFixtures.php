<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * This is a set of fixtures for the Department entities. It is not meant to be used in production.
 */
class E2EDepartmentFixtures extends Fixture implements FixtureGroupInterface
{
    public const string REFERENCE_1 = 'e2e-department-fixture-reference-1';
    public const string REFERENCE_2 = 'e2e-department-fixture-reference-2';
    public const string REFERENCE_3 = 'e2e-department-fixture-reference-3';

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['e2e'];
    }

    public function load(ObjectManager $manager): void
    {
        $entity = new Department();
        $entity->setName('E2E Test Department 1');
        $entity->setSlug('e2edep1');
        $entity->setShortTag('E2E-DEP1');
        $manager->persist($entity);
        $this->addReference(self::REFERENCE_1, $entity);

        $entity = new Department();
        $entity->setName('E2E Test Department 2');
        $entity->setSlug('e2edep2');
        $entity->setShortTag('E2E-DEP2');
        $manager->persist($entity);
        $this->addReference(self::REFERENCE_2, $entity);

        $entity = new Department();
        $entity->setName('E2E Test Department 3');
        $entity->setSlug('e2edep3');
        $entity->setShortTag('E2E-DEP3');
        $manager->persist($entity);
        $this->addReference(self::REFERENCE_3, $entity);

        $manager->flush();
    }
}
