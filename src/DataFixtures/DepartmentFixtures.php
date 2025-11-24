<?php

declare(strict_types=1);

namespace Shared\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Shared\Domain\Department\Department;

/**
 * This is a set of fixtures for the Department entities. It is not meant to be used in production.
 */
class DepartmentFixtures extends Fixture implements FixtureGroupInterface
{
    public const string REFERENCE_1 = 'department-fixture-reference-1';
    public const string REFERENCE_2 = 'department-fixture-reference-2';
    public const string REFERENCE_3 = 'department-fixture-reference-3';

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['example'];
    }

    public function load(ObjectManager $manager): void
    {
        $entity = new Department();
        $entity->setName('Voorbeeld bestuursorgaan 1');
        $entity->setSlug('vbb1');
        $entity->setShortTag('VBB1');
        $manager->persist($entity);
        $this->addReference(self::REFERENCE_1, $entity);

        $entity = new Department();
        $entity->setName('Voorbeeld bestuursorgaan 2');
        $entity->setSlug('vbb2');
        $entity->setShortTag('VBB2');
        $manager->persist($entity);
        $this->addReference(self::REFERENCE_2, $entity);

        $entity = new Department();
        $entity->setName('Voorbeeld bestuursorgaan 3');
        $entity->setSlug('vbb3');
        $entity->setShortTag('VBB3');
        $manager->persist($entity);
        $this->addReference(self::REFERENCE_3, $entity);

        $manager->flush();
    }
}
