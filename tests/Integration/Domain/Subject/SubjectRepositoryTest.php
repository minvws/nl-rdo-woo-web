<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Subject;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Uid\Uuid;

use function array_map;

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

    public function testFindWithPublishedLandingPageOnlyReturnsPublishedSubjects(): void
    {
        $subjectRepository = self::fromContainer(SubjectRepository::class);

        $publishedSubject = SubjectFactory::new()
            ->withLandingPage(SubjectLandingPageStatus::PUBLISHED, 'published-slug')
            ->create(['name' => 'published-subject']);

        SubjectFactory::new()
            ->withLandingPage(SubjectLandingPageStatus::CONCEPT, 'concept-slug')
            ->create(['name' => 'concept-subject']);

        SubjectFactory::createOne(['name' => 'no-landing-page']);

        $publishedWithoutSlug = SubjectFactory::createOne(['name' => 'published-without-slug']);
        $publishedWithoutSlug->setLandingPageStatus(SubjectLandingPageStatus::PUBLISHED);
        $subjectRepository->save($publishedWithoutSlug, true);

        $result = $subjectRepository->findWithPublishedLandingPage();

        self::assertEquals([$publishedSubject], $result);
    }

    public function testFindWithPublishedLandingPageLimitsResultsToMaxResults(): void
    {
        $subjectRepository = self::fromContainer(SubjectRepository::class);

        foreach (['a', 'b', 'c'] as $name) {
            SubjectFactory::new()
                ->withLandingPage(SubjectLandingPageStatus::PUBLISHED, $name . '-slug')
                ->create(['name' => $name]);
        }

        $result = $subjectRepository->findWithPublishedLandingPage(2);

        self::assertCount(2, $result);
    }

    public function testGetAllSubjectIdsWithLandingPageQueryIncludesEveryLandingPageStatus(): void
    {
        $subjectRepository = self::fromContainer(SubjectRepository::class);

        $publishedSubject = SubjectFactory::new()
            ->withLandingPage(SubjectLandingPageStatus::PUBLISHED, 'published-slug')
            ->create();
        $conceptSubject = SubjectFactory::new()
            ->withLandingPage(SubjectLandingPageStatus::CONCEPT, 'concept-slug')
            ->create();
        SubjectFactory::createOne(['name' => 'no-landing-page']);

        $subjectIds = [];
        foreach ($subjectRepository->getAllSubjectIdsWithLandingPageQuery()->toIterable() as $row) {
            self::assertIsArray($row);
            self::assertInstanceOf(Uuid::class, $row['id']);
            $subjectIds[] = $row['id']->toRfc4122();
        }

        self::assertEqualsCanonicalizing(
            [$publishedSubject->getId()->toRfc4122(), $conceptSubject->getId()->toRfc4122()],
            $subjectIds,
        );
        self::assertSame(2, $subjectRepository->countWithLandingPage());
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

    public function testGetSubjectsWithDuplicateNames(): void
    {
        $organisation = OrganisationFactory::createOne();
        $name = $this->getFaker()->unique()->word();

        $subject = SubjectFactory::createOne(['organisation' => $organisation, 'name' => $name]);
        $duplicateSubject = SubjectFactory::createOne(['organisation' => $organisation, 'name' => $name]);
        SubjectFactory::createOne(['organisation' => $organisation]);

        $subjectRepository = self::fromContainer(SubjectRepository::class);

        self::assertEqualsCanonicalizing(
            [$subject->getId(), $duplicateSubject->getId()],
            array_map(
                static fn (Subject $subject): Uuid => $subject->getId(),
                $subjectRepository->getSubjectsWithDuplicateName(),
            ),
        );
    }
}
