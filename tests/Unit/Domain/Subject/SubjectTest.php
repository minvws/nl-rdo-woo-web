<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Subject;

use Doctrine\Common\Collections\Collection;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;

class SubjectTest extends UnitTestCase
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
