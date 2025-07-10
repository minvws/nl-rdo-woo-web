<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Service\Security\User;
use App\Tests\Unit\UnitTestCase;
use App\Validator\SimilarityEmail;
use App\Validator\SimilarityEmailValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SimilarityEmailValidatorTest extends UnitTestCase
{
    public function testValidatorAddsNoViolationsForEmptyValue(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);

        $validator = new SimilarityEmailValidator($tokenStorage);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate('', new SimilarityEmail());
    }

    public function testValidatorAddsNoViolationWhenTokenIsMissing(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $tokenStorage->expects('getToken')->andReturnNull();

        $validator = new SimilarityEmailValidator($tokenStorage);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate('foo@bar.test', new SimilarityEmail());
    }

    public function testValidatorAddsNoViolationWhenUserIsMissing(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturnNull();
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $tokenStorage->expects('getToken')->twice()->andReturn($token);

        $validator = new SimilarityEmailValidator($tokenStorage);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate('foo@bar.test', new SimilarityEmail());
    }

    public function testValidatorAddsViolationForPasswordSimilarToEmail(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $token = \Mockery::mock(TokenInterface::class);
        $user = \Mockery::mock(User::class);
        $user->expects('getEmail')->andReturn('fooo@bar.text');
        $token->expects('getUser')->andReturn($user);
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $tokenStorage->expects('getToken')->twice()->andReturn($token);

        $input = 'foo@bar.test';

        $validator = new SimilarityEmailValidator($tokenStorage);
        $validator->initialize($context);

        $builder = \Mockery::mock(ConstraintViolationBuilderInterface::class);
        $context->expects('buildViolation')->andReturn($builder);
        $builder->expects('setParameter');
        $builder->expects('addViolation');

        $validator->validate($input, new SimilarityEmail());
    }

    public function testValidatorAddsNoViolationForPasswordNotSimilarToEmail(): void
    {
        $context = \Mockery::mock(ExecutionContextInterface::class);
        $token = \Mockery::mock(TokenInterface::class);
        $user = \Mockery::mock(User::class);
        $user->expects('getEmail')->andReturn('user@some.domain');
        $token->expects('getUser')->andReturn($user);
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $tokenStorage->expects('getToken')->twice()->andReturn($token);

        $input = 'foo@bar.test';

        $validator = new SimilarityEmailValidator($tokenStorage);
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate($input, new SimilarityEmail());
    }
}
