<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\NotTheSamePassword;
use Shared\Validator\NotTheSamePasswordValidator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NotTheSamePasswordValidatorTest extends UnitTestCase
{
    public function testValidatorAddsNoViolationsForNullValue(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $hasher = \Mockery::mock(UserPasswordHasherInterface::class);
        $security = \Mockery::mock(Security::class);

        $validator = new NotTheSamePasswordValidator($hasher, $security);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate(null, new NotTheSamePassword());
    }

    public function testValidatorAddsViolationForSamePassword(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $hasher = \Mockery::mock(UserPasswordHasherInterface::class);
        $user = \Mockery::mock(User::class);
        $security = \Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn($user);

        $validator = new NotTheSamePasswordValidator($hasher, $security);
        $validator->initialize($context);

        $input = 'foo';
        $hasher->expects('isPasswordValid')->with($user, $input)->andReturnTrue();

        $builder = \Mockery::mock(ConstraintViolationBuilderInterface::class);
        $context->expects('buildViolation')->andReturn($builder);
        $builder->expects('setParameter');
        $builder->expects('addViolation');

        $validator->validate($input, new NotTheSamePassword());
    }

    public function testValidatorAddsNoViolationForDifferentPassword(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $hasher = \Mockery::mock(UserPasswordHasherInterface::class);
        $user = \Mockery::mock(User::class);
        $security = \Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn($user);

        $validator = new NotTheSamePasswordValidator($hasher, $security);
        $validator->initialize($context);

        $input = 'foo';
        $hasher->expects('isPasswordValid')->with($user, $input)->andReturnFalse();

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate($input, new NotTheSamePassword());
    }
}
