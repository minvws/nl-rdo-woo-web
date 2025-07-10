<?php

declare(strict_types=1);

namespace App\Vws\DataFixtures;

use App\Domain\Department\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * This is a set of fixtures for the Department entities. It is not meant to be used in production.
 */
class DepartmentFixtures extends Fixture implements FixtureGroupInterface
{
    public const string REFERENCE = 'vws-department-fixture-reference';

    /**
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return ['vws'];
    }

    public function load(ObjectManager $manager): void
    {
        $entity = new Department();
        $entity->setName('ministerie van Volksgezondheid, Welzijn en Sport');
        $entity->setSlug('VWS');
        $entity->setShortTag('VWS');
        $manager->persist($entity);
        $this->addReference(self::REFERENCE, $entity);

        $manager->flush();
    }
}
