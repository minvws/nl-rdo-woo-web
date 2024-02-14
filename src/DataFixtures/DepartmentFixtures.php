<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Department;
use App\Enum\Department as EnumDepartment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * This is a set of fixtures for the Department entities. It is not meant to be used in production.
 */
class DepartmentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (EnumDepartment::cases() as $department) {
            $entity = new Department();
            $entity->setName($department->value);
            $entity->setShortTag($department->getShortTag());
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
