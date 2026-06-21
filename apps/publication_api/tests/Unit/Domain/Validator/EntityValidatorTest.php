<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Mockery;
use PublicationApi\Domain\Validator\EntityValidator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidatorTest extends UnitTestCase
{
    private ValidatorInterface&Mockery\MockInterface $validator;
    private EntityManagerInterface&Mockery\MockInterface $entityManager;
    private EntityValidator $entityValidator;

    public function setUp(): void
    {
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->entityValidator = new EntityValidator($this->validator, $this->entityManager);
    }

    public function testNoActionForValidEntity(): void
    {
        $entity = Mockery::mock(Subject::class);
        $this->validator->expects('validate')->with($entity)->andReturns(new ConstraintViolationList());

        $this->entityValidator->throwExceptionIfNotValid($entity);
    }

    public function testExceptionThrownAndEntityRefreshedForInvalidExistingEntity(): void
    {
        $entity = Mockery::mock(Subject::class);

        $violation = new ConstraintViolation('message', 'message', [], '', '', $entity);
        $violationList = new ConstraintViolationList([$violation]);

        $this->validator->expects('validate')->with($entity)->andReturns($violationList);

        $unitOfWork = Mockery::mock(UnitOfWork::class);
        $unitOfWork->expects('isScheduledForInsert')->with($entity)->andReturnFalse();

        $this->entityManager->expects('getUnitOfWork')->andReturn($unitOfWork);
        $this->entityManager->expects('contains')->with($entity)->andReturnTrue();

        $this->entityManager->expects('refresh')->with($entity);

        $this->expectExceptionObject(new ValidationException($violationList));

        $this->entityValidator->throwExceptionIfNotValid($entity);
    }

    public function testExceptionThrownButNoRefreshForNewEntity(): void
    {
        $entity = Mockery::mock(Subject::class);

        $violation = new ConstraintViolation('message', 'message', [], '', '', $entity);
        $violationList = new ConstraintViolationList([$violation]);

        $this->validator->expects('validate')->with($entity)->andReturns($violationList);

        $unitOfWork = Mockery::mock(UnitOfWork::class);
        $unitOfWork->expects('isScheduledForInsert')->with($entity)->andReturnTrue();

        $this->entityManager->expects('getUnitOfWork')->andReturn($unitOfWork);
        $this->entityManager->expects('contains')->with($entity)->andReturnTrue();

        $this->expectExceptionObject(new ValidationException($violationList));

        $this->entityValidator->throwExceptionIfNotValid($entity);
    }
}
