<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Entity\Department;
use App\Entity\Organisation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * This is a set of fixtures for the Organisation entity. It is not meant to be used in production.
 */
class OrganisationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $org = $manager->getRepository(Organisation::class)->findOneBy(['name' => 'Programmadirectie Openbaarheid']);
        if ($org !== null) {
            return;
        }

        /** @var Department $department */
        $department = $manager->getRepository(Department::class)->findOneBy(['shortTag' => 'VWS']);

        $documentPrefix = new DocumentPrefix();
        $documentPrefix->setPrefix('MINVWS');

        $entity = new Organisation();
        $entity->setName('Programmadirectie Openbaarheid');
        $entity->addDepartment($department);
        $entity->addDocumentPrefix($documentPrefix);
        $manager->persist($entity);
        $manager->flush();
    }
}
