<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Mockery;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CovenantTest extends SharedWebTestCase
{
    public function testStartDateValidationAllowsADate10YearsBeforeCreationDate(): void
    {
        $department = Mockery::mock(Department::class);
        $date = new DateTimeImmutable('2020-04-21');

        $covenant = new Covenant();
        $covenant->setDossierNr('foo-123');
        $covenant->setDepartments([$department]);
        $covenant->setTitle('bar');
        $covenant->setDocumentPrefix('FOO');
        $covenant->setCreatedAt($date);
        $covenant->setDateFrom(PlainDate::create($date->modify('-10 years')->format('Y-m-d')));

        $validator = self::fromContainer(ValidatorInterface::class);

        /** @var ConstraintViolationListInterface $errors */
        $errors = $validator->validate($covenant, null, [DossierValidationGroup::DETAILS->value]);
        self::assertCount(0, $errors);
    }

    public function testStartDateValidationWithoutACreatedAt(): void
    {
        $department = Mockery::mock(Department::class);
        $date = new DateTimeImmutable('2020-04-21');
        CarbonImmutable::setTestNow($date);

        $covenant = new Covenant();
        $covenant->setDossierNr('foo-123');
        $covenant->setDepartments([$department]);
        $covenant->setTitle('bar');
        $covenant->setDocumentPrefix('FOO');
        $covenant->setDateFrom(PlainDate::create($date->modify('-10 years')->format('Y-m-d')));

        $validator = self::fromContainer(ValidatorInterface::class);

        /** @var ConstraintViolationListInterface $errors */
        $errors = $validator->validate($covenant, null, [DossierValidationGroup::DETAILS->value]);
        self::assertCount(0, $errors);
    }
}
