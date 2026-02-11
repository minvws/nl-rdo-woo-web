<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\CommonList;
use Shared\Validator\CommonListValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class CommonListValidatorTest extends UnitTestCase
{
    #[DataProvider('validatorProvider')]
    public function testValidator(string $input, bool $expectViolation): void
    {
        $context = Mockery::mock(ExecutionContextInterface::class);
        if ($expectViolation) {
            $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
            $context->expects('buildViolation')->andReturn($builder);
            $builder->expects('setParameter');
            $builder->expects('addViolation');
        } else {
            $context->shouldNotHaveReceived('buildViolation');
        }

        $validator = new CommonListValidator();
        $validator->initialize($context);

        $validator->validate($input, new CommonList());
    }

    /**
     * @return array<string,array{input:string,expectViolation:bool}>
     */
    public static function validatorProvider(): array
    {
        return [
            'empty-string' => [
                'input' => '',
                'expectViolation' => false,
            ],
            'common-password' => [
                'input' => 'matrix',
                'expectViolation' => true,
            ],
            'similar-to-common-password' => [
                'input' => 'matrixx',
                'expectViolation' => true,
            ],
        ];
    }
}
