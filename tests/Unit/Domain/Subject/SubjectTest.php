<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Subject;

use App\Domain\Publication\Subject\Subject;
use App\Entity\Organisation;
use Doctrine\Common\Collections\Collection;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class SubjectTest extends MockeryTestCase
{
    public function testGettersAndSetters(): void
    {
        $subject = new Subject();
        self::assertNotEmpty($subject->getId()->toRfc4122());

        $subject->setName($name = 'foo');
        self::assertEquals($name, $subject->getName());

        $subject->setOrganisation($organisation = \Mockery::mock(Organisation::class));
        self::assertEquals($organisation, $subject->getOrganisation());

        $subject->setDossiers($dossiers = \Mockery::mock(Collection::class));
        self::assertEquals($dossiers, $subject->getDossiers());
    }
}
