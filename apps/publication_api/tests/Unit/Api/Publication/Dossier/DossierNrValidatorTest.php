<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\DossierNrValidator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\UniqueDossierNr;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DossierNrValidatorTest extends UnitTestCase
{
    public function testNoExceptionWhenNoViolations(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->andReturn(new ConstraintViolationList());

        $dossierNrValidator = new DossierNrValidator($validator);
        $dossierNrValidator->validate('some-nr', 'prefix');
    }

    public function testExceptionWithDossierNumberPropertyPathWhenViolationExists(): void
    {
        $violation = new ConstraintViolation(
            'dossier.dossier_nr_not_unique',
            'dossier.dossier_nr_not_unique',
            [],
            'some-nr',
            '',
            'some-nr',
        );

        $violations = new ConstraintViolationList([$violation]);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->andReturn($violations);

        $dossierNrValidator = new DossierNrValidator($validator);

        $this->expectException(ValidationException::class);

        $dossierNrValidator->validate('some-nr', 'prefix');
    }

    public function testValidationExceptionHasDossierNumberPropertyPath(): void
    {
        $violation = new ConstraintViolation(
            'dossier.dossier_nr_not_unique',
            'dossier.dossier_nr_not_unique',
            [],
            'some-nr',
            '',
            'some-nr',
        );

        $violations = new ConstraintViolationList([$violation]);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->andReturn($violations);

        $dossierNrValidator = new DossierNrValidator($validator);

        self::expectException(ValidationException::class);
        $dossierNrValidator->validate('some-nr', 'prefix');
    }

    public function testPassesDocumentPrefixAndExcludeIdToConstraint(): void
    {
        $excludeId = Uuid::v6();

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with(
                'some-nr',
                Mockery::on(static function (UniqueDossierNr $constraint) use ($excludeId): bool {
                    return $constraint->documentPrefix === 'prefix'
                        && $constraint->excludeId === $excludeId;
                }),
            )
            ->andReturn(new ConstraintViolationList());

        $dossierNrValidator = new DossierNrValidator($validator);
        $dossierNrValidator->validate('some-nr', 'prefix', $excludeId);
    }
}
