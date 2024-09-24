<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Subject;

use App\Domain\Publication\Subject\Subject;
use App\Domain\Publication\Subject\SubjectRepository;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SubjectRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): SubjectRepository
    {
        /** @var SubjectRepository */
        return self::getContainer()->get(SubjectRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testSaveAndRemove(): void
    {
        $organisation = OrganisationFactory::createOne();

        $subject = new Subject();
        $subject->setName('foo');
        $subject->setOrganisation($organisation->_real());

        $repository = $this->getRepository();
        self::assertNull(
            $this->getRepository()->find($subject->getId())
        );

        $repository->save($subject, true);
        $result = $this->getRepository()->find($subject->getId());
        self::assertEquals($subject, $result);

        $repository->remove($subject, true);
        self::assertNull(
            $this->getRepository()->find($subject->getId())
        );
    }

    public function testGetQueryForOrganisationDoesNotReturnSubjectFromOtherOrganisation(): void
    {
        $repository = $this->getRepository();

        $organisationA = OrganisationFactory::createOne();
        $organisationB = OrganisationFactory::createOne();

        $subjectA = new Subject();
        $subjectA->setName('foo');
        $subjectA->setOrganisation($organisationA->_real());
        $repository->save($subjectA, true);

        $subjectB = new Subject();
        $subjectB->setName('bar');
        $subjectB->setOrganisation($organisationB->_real());
        $repository->save($subjectB, true);

        $query = $repository->getQueryForOrganisation($organisationA->_real());
        $result = $query->getResult();

        self::assertEquals([$subjectA], $result);
    }
}
