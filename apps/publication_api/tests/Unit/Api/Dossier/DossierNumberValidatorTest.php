<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\DossierNumberValidator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\UniqueDossierNumber;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function sprintf;

final class DossierNumberValidatorTest extends UnitTestCase
{
    public function testValidatePassesTheDocumentPrefixAndExcludeIdToTheConstraint(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $excludeId = Uuid::v6();

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->with(
            $dossierNumber,
            Mockery::on(static function (UniqueDossierNumber $uniqueDossierNumber) use ($documentPrefix, $excludeId): bool {
                if ($uniqueDossierNumber->documentPrefix !== $documentPrefix) {
                    return false;
                }

                if ($uniqueDossierNumber->excludeId !== $excludeId) {
                    return false;
                }

                return true;
            }),
        )->andReturn(new ConstraintViolationList());

        $dossierNumberValidator = new DossierNumberValidator($validator);

        $dossierNumberValidator->validate($dossierNumber, $documentPrefix, $excludeId);
    }

    public function testValidateThrowsWithTheViolationMappedOntoTheDossierNumberProperty(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $message = $this->getFaker()->sentence();

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')->andReturn(new ConstraintViolationList([
            new ConstraintViolation($message, null, [], null, 'value', $dossierNumber),
        ]));

        $dossierNumberValidator = new DossierNumberValidator($validator);

        self::expectException(ValidationException::class);
        self::expectExceptionMessageIs(sprintf('dossierNumber: %s', $message));

        $dossierNumberValidator->validate($dossierNumber, $documentPrefix);
    }
}
