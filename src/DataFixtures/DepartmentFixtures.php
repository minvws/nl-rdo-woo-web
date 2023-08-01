<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Department;
use App\Entity\GovernmentOfficial;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DepartmentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $departments = [
            'Ministerie van Algemene Zaken',
            'Ministerie van Binnenlandse Zaken en Koninkrijksrelaties',
            'Ministerie van Buitenlandse Zaken',
            'Ministerie van Defensie',
            'Ministerie van Economische Zaken en Klimaat',
            'Ministerie van Financiën',
            'Ministerie van Infrastructuur en Waterstaat',
            'Ministerie van Justitie en Veiligheid',
            'Ministerie van Landbouw, Natuur en Voedselkwaliteit',
            'Ministerie van Onderwijs, Cultuur en Wetenschap',
            'Ministerie van Sociale Zaken en Werkgelegenheid',
            'Ministerie van Volksgezondheid, Welzijn en Sport',
        ];

        foreach ($departments as $department) {
            $entity = new Department();
            $entity->setName($department);
            $manager->persist($entity);
        }

        $heads = [
            'Minister-President Mark Rutte',
            'Minister Kajsa Ollongren',
            'Minister Stef Blok',
            'Minister Ank Bijleveld-Schouten',
            'Minister Eric Wiebes',
            'Minister Wopke Hoekstra',
            'Minister Cora van Nieuwenhuizen',
            'Minister Ferd Grapperhaus',
            'Minister Carola Schouten',
            'Minister Ingrid van Engelshoven',
            'Minister Wouter Koolmees',
            'Minister Hugo de Jonge',
            'Staatssecretaris Raymond Knops',
            'Staatssecretaris Mona Keijzer',
            'Staatssecretaris Stientje van Veldhoven',
            'Staatssecretaris Paul Blokhuis',
            'Staatssecretaris Tamara van Ark',
            'Staatssecretaris Barbara Visser',
            'Staatssecretaris Ankie Broekers-Knol',
            'Staatssecretaris Alexandra van Huffelen',
            'Staatssecretaris Hans Vijlbrief',
            'Staatssecretaris Bas van \'t Wout',
        ];

        foreach ($heads as $head) {
            $entity = new GovernmentOfficial();
            $entity->setName($head);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
