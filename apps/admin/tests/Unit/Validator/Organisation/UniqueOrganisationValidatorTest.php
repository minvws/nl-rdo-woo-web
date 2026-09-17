<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Validator\Organisation;

use Admin\Form\Organisation\OrganisationFormData;
use Admin\Validator\Organisation\UniqueOrganisation;
use Admin\Validator\Organisation\UniqueOrganisationValidator;
use Mockery;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class UniqueOrganisationValidatorTest extends UnitTestCase
{
    public function testNoViolationWhenValuesAreAvailable(): void
    {
        $repository = Mockery::mock(OrganisationRepository::class);
        $repository->expects('findOneBy')->with(['name' => 'New organisation'])->andReturnNull();
        $repository->expects('findOneBy')->with(['prefix' => $prefix = OrganisationPrefix::create('NEW-01')])->andReturnNull();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')->never();

        $validator = new UniqueOrganisationValidator($repository);
        $validator->initialize($context);
        $validator->validate(
            new OrganisationFormData(name: 'New organisation', prefix: $prefix),
            new UniqueOrganisation(),
        );
    }

    public function testNoViolationWhenExistingOrganisationIsExcluded(): void
    {
        $id = Uuid::v6();
        $existing = Mockery::mock(Organisation::class);
        $existing->expects('getId')->twice()->andReturn($id);

        $repository = Mockery::mock(OrganisationRepository::class);
        $repository->expects('findOneBy')->with(['name' => 'Organisation name'])->andReturn($existing);
        $repository->expects('findOneBy')->with(['prefix' => $prefix = OrganisationPrefix::create('ABC-12')])->andReturn($existing);

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')->never();

        $validator = new UniqueOrganisationValidator($repository);
        $validator->initialize($context);
        $validator->validate(
            new OrganisationFormData(
                name: 'Organisation name',
                prefix: $prefix,
                organisationId: $id,
            ),
            new UniqueOrganisation(),
        );
    }

    public function testAddsViolationsForExistingNameAndPrefix(): void
    {
        $existingName = Mockery::mock(Organisation::class);
        $existingPrefix = Mockery::mock(Organisation::class);

        $repository = Mockery::mock(OrganisationRepository::class);
        $repository->expects('findOneBy')->with(['name' => 'Organisation name'])->andReturn($existingName);
        $repository->expects('findOneBy')->with(['prefix' => $prefix = OrganisationPrefix::create('ABC-12')])->andReturn($existingPrefix);

        $nameBuilder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $nameBuilder->expects('atPath')->with('name')->andReturnSelf();
        $nameBuilder->expects('addViolation');
        $prefixBuilder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $prefixBuilder->expects('atPath')->with('prefix')->andReturnSelf();
        $prefixBuilder->expects('addViolation');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')->with('This organisation already exists.')->andReturn($nameBuilder);
        $context->expects('buildViolation')->with('organisation.prefix_already_exists')->andReturn($prefixBuilder);

        $validator = new UniqueOrganisationValidator($repository);
        $validator->initialize($context);
        $validator->validate(
            new OrganisationFormData(name: 'Organisation name', prefix: $prefix),
            new UniqueOrganisation(),
        );
    }
}
