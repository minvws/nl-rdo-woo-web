<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Subject;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Integration\SharedWebTestCase;

class SubjectRepositoryTest extends SharedWebTestCase
{
    public function testSaveAndRemove(): void
    {
        $organisation = OrganisationFactory::createOne();

        $subject = new Subject();
        $subject->setName('foo');
        $subject->setOrganisation($organisation);

        $subjectRepository = self::fromContainer(SubjectRepository::class);
        self::assertNull($subjectRepository->find($subject->getId()));

        $subjectRepository->save($subject, true);
        $result = $subjectRepository->find($subject->getId());
        self::assertEquals($subject, $result);

        $subjectRepository->remove($subject, true);
        self::assertNull($subjectRepository->find($subject->getId()));
    }

    public function testGetQueryForOrganisationDoesNotReturnSubjectFromOtherOrganisation(): void
    {
        $subjectRepository = self::fromContainer(SubjectRepository::class);

        $organisationA = OrganisationFactory::createOne();
        $organisationB = OrganisationFactory::createOne();

        $subjectA = new Subject();
        $subjectA->setName('foo');
        $subjectA->setOrganisation($organisationA);
        $subjectRepository->save($subjectA, true);

        $subjectB = new Subject();
        $subjectB->setName('bar');
        $subjectB->setOrganisation($organisationB);
        $subjectRepository->save($subjectB, true);

        $query = $subjectRepository->getQueryForOrganisation($organisationA);
        $result = $query->getResult();

        self::assertEquals([$subjectA], $result);
    }

    public function testIsInUseReturnsFalseForUnusedSubject(): void
    {
        $subject = SubjectFactory::createOne();
        $subjectRepository = self::fromContainer(SubjectRepository::class);

        self::assertFalse($subjectRepository->isInUse($subject));
    }

    public function testIsInUseReturnsTrueForSubjectLinkedToDossier(): void
    {
        $subject = SubjectFactory::createOne();
        WooDecisionFactory::createOne(['subject' => $subject]);

        $subjectRepository = self::fromContainer(SubjectRepository::class);
        self::assertTrue($subjectRepository->isInUse($subject));
    }
}
