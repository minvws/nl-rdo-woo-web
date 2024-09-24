<?php

declare(strict_types=1);

namespace App\Tests\Integration\DataFixtures;

use App\DataFixtures\DepartmentFixtures;
use App\Entity\Department;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DepartmentFixturesTest extends KernelTestCase
{
    use IntegrationTestTrait;

    public function testLoad(): void
    {
        /** @var EntityManager $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        // Clear the table (there might be global state data)
        $em->createQueryBuilder()->delete(Department::class, 'd')->getQuery()->execute();

        // A double check that the table is empty
        $currentCount = $em->getRepository(Department::class)->count([]);
        $this->assertSame(0, $currentCount, 'The Department table is not empty');

        /** @var DepartmentFixtures $df */
        $df = self::getContainer()->get(DepartmentFixtures::class);
        $df->load($em);

        $expected = count(DepartmentEnum::cases());

        $allDeps = $em->getRepository(Department::class)->findBy([], ['slug' => 'ASC']);

        $mappedAllDeps = array_map(fn (Department $d) => [
            'name' => $d->getName(),
            'shortTag' => $d->getShortTag(),
            'slug' => $d->getSlug(),
            'isPublic' => $d->isPublic(),
        ], $allDeps);

        $this->assertSame($expected, count($allDeps), 'The number of departments is not as expected');
        $this->assertMatchesYamlSnapshot($mappedAllDeps);
    }
}
