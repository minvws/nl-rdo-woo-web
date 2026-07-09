<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\DossierNumberValidator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\UniqueDossierNumber;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DossierNumberValidatorTest extends UnitTestCase
{
    public function testNoExceptionWhenNoViolations(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->andReturn(new ConstraintViolationList());

        $dossierNumberValidator = new DossierNumberValidator($validator);
        $dossierNumberValidator->validate('some-number', 'prefix');
    }

    public function testExceptionWithDossierNumberPropertyPathWhenViolationExists(): void
    {
        $violation = new ConstraintViolation(
            'dossier.dossier_number_not_unique',
            'dossier.dossier_number_not_unique',
            [],
            'some-number',
            '',
            'some-number',
        );

        $violations = new ConstraintViolationList([$violation]);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->andReturn($violations);

        $dossierNumberValidator = new DossierNumberValidator($validator);

        $this->expectException(ValidationException::class);

        $dossierNumberValidator->validate('some-number', 'prefix');
    }

    public function testValidationExceptionHasDossierNumberPropertyPath(): void
    {
        $violation = new ConstraintViolation(
            'dossier.dossier_number_not_unique',
            'dossier.dossier_number_not_unique',
            [],
            'some-number',
            '',
            'some-number',
        );

        $violations = new ConstraintViolationList([$violation]);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->andReturn($violations);

        $dossierNumberValidator = new DossierNumberValidator($validator);

        self::expectException(ValidationException::class);
        $dossierNumberValidator->validate('some-number', 'prefix');
    }

    public function testPassesDocumentPrefixAndExcludeIdToConstraint(): void
    {
        $excludeId = Uuid::v6();

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with(
                'some-number',
                Mockery::on(static function (UniqueDossierNumber $constraint) use ($excludeId): bool {
                    return $constraint->documentPrefix === 'prefix'
                        && $constraint->excludeId === $excludeId;
                }),
            )
            ->andReturn(new ConstraintViolationList());

        $dossierNumberValidator = new DossierNumberValidator($validator);
        $dossierNumberValidator->validate('some-number', 'prefix', $excludeId);
    }
}
