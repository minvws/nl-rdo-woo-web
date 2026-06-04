<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use Mockery;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\AllowedFileExtension;
use Shared\Validator\AllowedFileExtensionValidator;
use Shared\ValueObject\FileName;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AllowedFileExtensionValidatorTest extends UnitTestCase
{
    public function testNoViolationForNullValue(): void
    {
        $context = Mockery::mock(ExecutionContextInterface::class);

        $validator = new AllowedFileExtensionValidator();
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate(null, new AllowedFileExtension(UploadGroupId::API_WOO_DECISION_DOCUMENTS));
    }

    public function testNoViolationForAllowedExtension(): void
    {
        $context = Mockery::mock(ExecutionContextInterface::class);

        $validator = new AllowedFileExtensionValidator();
        $validator->initialize($context);

        $context->shouldNotHaveReceived('buildViolation');

        $validator->validate(FileName::create('document.pdf'), new AllowedFileExtension(UploadGroupId::API_WOO_DECISION_DOCUMENTS));
    }

    public function testViolationForDisallowedExtension(): void
    {
        $context = Mockery::mock(ExecutionContextInterface::class);

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $context->expects('buildViolation')->andReturn($builder);
        $builder->expects('setParameter')
            ->with('{{ extension }}', 'exe')
            ->once()
            ->andReturn($builder);
        $builder->expects('setParameter')
            ->with('{{ allowed }}', Mockery::type('string'))
            ->once()
            ->andReturn($builder);
        $builder->expects('setCode')
            ->with(AllowedFileExtension::INVALID_EXTENSION_ERROR)
            ->andReturn($builder);
        $builder->expects('addViolation');

        $validator = new AllowedFileExtensionValidator();
        $validator->initialize($context);

        $validator->validate(FileName::create('document.exe'), new AllowedFileExtension(UploadGroupId::API_WOO_DECISION_DOCUMENTS));
    }
}
