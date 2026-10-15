<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Service\MainDocumentService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function sprintf;

class DossierMainDocumentValidatorTest extends UnitTestCase
{
    public function testValidateSucceeds(): void
    {
        $mainDocumentService = Mockery::mock(MainDocumentService::class);
        $dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);
        $dossierWorkflowManager->expects('isTransitionAllowed')->never();
        $dossierMainDocumentValidator = new DossierMainDocumentValidator($mainDocumentService, $dossierWorkflowManager);

        $mainDocument = $this->createMainDocument();

        $mainDocumentService->expects('validate')
            ->with(
                $mainDocument,
                [
                    DossierValidationGroup::DETAILS->value,
                    DossierValidationGroup::DECISION->value,
                    DossierValidationGroup::DOCUMENTS->value,
                    DossierValidationGroup::PUBLICATION->value,
                    DossierValidationGroup::CONTENT->value,
                    Constraint::DEFAULT_GROUP,
                ],
            );

        $dossierMainDocumentValidator->validate($mainDocument);
    }

    public function testValidateRefreshesAndRethrowsOnValidationFailure(): void
    {
        $mainDocumentService = Mockery::mock(MainDocumentService::class);
        $dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);
        $dossierWorkflowManager->expects('isTransitionAllowed')->never();
        $dossierMainDocumentValidator = new DossierMainDocumentValidator($mainDocumentService, $dossierWorkflowManager);

        $message = self::getFaker()->sentence();
        $mainDocument = $this->createMainDocument();
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                $message,
                null,
                [],
                null,
                'fileName',
                null,
                null,
                null,
            ),
        ]);
        $validationFailedException = new ValidationFailedException($mainDocument, $violations);

        $mainDocumentService->expects('validate')
            ->andThrow($validationFailedException);

        $mainDocumentService->expects('refreshMainDocument')
            ->with($mainDocument);

        self::expectException(ValidationException::class);
        self::expectExceptionMessageIs(sprintf('mainDocument.fileName: %s', $message));

        $dossierMainDocumentValidator->validate($mainDocument);
    }

    public function testValidateDeleteSucceedsWhenTransitionIsAllowed(): void
    {
        $mainDocumentService = Mockery::mock(MainDocumentService::class);
        $dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);
        $dossier = Mockery::mock(AbstractDossier::class);

        $dossierWorkflowManager->expects('isTransitionAllowed')
            ->with($dossier, DossierStatusTransition::DELETE_MAIN_DOCUMENT)
            ->andReturnTrue();

        $dossierMainDocumentValidator = new DossierMainDocumentValidator($mainDocumentService, $dossierWorkflowManager);

        $dossierMainDocumentValidator->validateDelete($dossier);
    }

    public function testValidateDeleteThrowsWhenTransitionIsNotAllowed(): void
    {
        $mainDocumentService = Mockery::mock(MainDocumentService::class);
        $dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);
        $dossier = Mockery::mock(AbstractDossier::class);

        $dossierWorkflowManager->expects('isTransitionAllowed')
            ->with($dossier, DossierStatusTransition::DELETE_MAIN_DOCUMENT)
            ->andReturnFalse();

        $dossierMainDocumentValidator = new DossierMainDocumentValidator($mainDocumentService, $dossierWorkflowManager);

        try {
            $dossierMainDocumentValidator->validateDelete($dossier);
            self::fail('Expected a validation exception');
        } catch (ValidationException $exception) {
            $violations = $exception->getConstraintViolationList();

            self::assertCount(1, $violations);
            $violation = $violations->get(0);
            self::assertSame('mainDocument', $violation->getPropertyPath());
            self::assertSame('The "mainDocument" cannot be modified', $violation->getMessage());
            self::assertSame(ConstraintViolationBuilder::MODIFIED_SUB_ENTITY_ERROR, $violation->getCode());
        }
    }

    private function createMainDocument(): AbstractMainDocument&MockInterface
    {
        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::CONCEPT);

        $mainDocument = Mockery::mock(AbstractMainDocument::class);
        $mainDocument->expects('getDossier')
            ->andReturn($dossier);

        return $mainDocument;
    }
}
