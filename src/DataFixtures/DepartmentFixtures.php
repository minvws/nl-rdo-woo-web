<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * This is a set of fixtures for the Department entities. It is not meant to be used in production.
 */
class DepartmentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $departments = [
            'Ministerie van Algemene Zaken' => 'AZ',
            'Ministerie van Binnenlandse Zaken en Koninkrijksrelaties' => 'BZK',
            'Ministerie van Buitenlandse Zaken' => 'BZ',
            'Ministerie van Defensie' => 'Def',
            'Ministerie van Economische Zaken en Klimaat' => 'EZK',
            'Ministerie van Financiën' => 'Fin',
            'Ministerie van Infrastructuur en Waterstaat' => 'I&W',
            'Ministerie van Justitie en Veiligheid' => 'J&V',
            'Ministerie van Landbouw, Natuur en Voedselkwaliteit' => 'LNV',
            'Ministerie van Onderwijs, Cultuur en Wetenschap' => 'OCW',
            'Ministerie van Sociale Zaken en Werkgelegenheid' => 'SZW',
            'Ministerie van Volksgezondheid, Welzijn en Sport' => 'VWS',
        ];

        foreach ($departments as $department => $short) {
            $entity = new Department();
            $entity->setName($department);
            $entity->setShortTag($short);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
