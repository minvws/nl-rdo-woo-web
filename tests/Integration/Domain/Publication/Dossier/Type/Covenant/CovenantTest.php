<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Department\Department;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CovenantTest extends KernelTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testStartDateValidationAllowsADate10YearsBeforeCreationDate(): void
    {
        $department = \Mockery::mock(Department::class);
        $date = new \DateTimeImmutable('2020-04-21');

        $covenant = new Covenant();
        $covenant->setDossierNr('foo-123');
        $covenant->setDepartments([$department]);
        $covenant->setTitle('bar');
        $covenant->setDocumentPrefix('FOO');
        $covenant->setCreatedAt($date);
        $covenant->setDateFrom($date->modify('-10 years'));

        $validator = self::getContainer()->get(ValidatorInterface::class);

        /** @var ConstraintViolationListInterface $errors */
        $errors = $validator->validate($covenant, null, [DossierValidationGroup::DETAILS->value]);
        self::assertCount(0, $errors);
    }

    public function testStartDateValidationWithoutACreatedAt(): void
    {
        $department = \Mockery::mock(Department::class);
        $date = new \DateTimeImmutable('2020-04-21');
        CarbonImmutable::setTestNow($date);

        $covenant = new Covenant();
        $covenant->setDossierNr('foo-123');
        $covenant->setDepartments([$department]);
        $covenant->setTitle('bar');
        $covenant->setDocumentPrefix('FOO');
        $covenant->setDateFrom($date->modify('-10 years'));

        $validator = self::getContainer()->get(ValidatorInterface::class);

        /** @var ConstraintViolationListInterface $errors */
        $errors = $validator->validate($covenant, null, [DossierValidationGroup::DETAILS->value]);
        self::assertCount(0, $errors);
    }
}
