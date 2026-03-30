<?php

declare(strict_types=1);

namespace Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Service\MainDocumentService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MainDocumentServiceTest extends UnitTestCase
{
    public function testValidate(): void
    {
        $mainDocument = Mockery::mock(DispositionMainDocument::class);

        $constraintViolationList = Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(0);

        $entityManager = Mockery::mock(EntityManagerInterface::class);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($mainDocument)
            ->andReturn($constraintViolationList);

        $mainDocumentService = new MainDocumentService($entityManager, $validator);
        $mainDocumentService->validate($mainDocument);
    }

    public function testValidateWithErrors(): void
    {
        $mainDocument = Mockery::mock(DispositionMainDocument::class);

        $constraintViolationList = Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(1);

        $entityManager = Mockery::mock(EntityManagerInterface::class);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($mainDocument)
            ->andReturn($constraintViolationList);

        $mainDocumentService = new MainDocumentService($entityManager, $validator);

        $this->expectException(ValidationFailedException::class);
        $mainDocumentService->validate($mainDocument);
    }

    public function testRefreshMainDocument(): void
    {
        $mainDocument1 = Mockery::mock(DispositionMainDocument::class);
        $mainDocument2 = Mockery::mock(DispositionMainDocument::class);
        $mainDocument3 = Mockery::mock(DispositionMainDocument::class);

        $unitOfWork = Mockery::mock(UnitOfWork::class);
        $unitOfWork->expects('isScheduledForInsert')
            ->with($mainDocument2)
            ->andReturnTrue();
        $unitOfWork->expects('isScheduledForInsert')
            ->with($mainDocument3)
            ->andReturnFalse();

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('getUnitOfWork')
            ->times(3)
            ->andReturn($unitOfWork);
        $entityManager->expects('contains')
            ->with($mainDocument1)
            ->andReturnFalse();
        $entityManager->expects('contains')
            ->with($mainDocument2)
            ->andReturnTrue();
        $entityManager->expects('contains')
            ->with($mainDocument3)
            ->andReturnTrue();
        $entityManager->expects('refresh')
            ->with($mainDocument3);

        $validator = Mockery::mock(ValidatorInterface::class);

        $mainDocumentService = new MainDocumentService($entityManager, $validator);
        $mainDocumentService->refreshMainDocument($mainDocument1);
        $mainDocumentService->refreshMainDocument($mainDocument2);
        $mainDocumentService->refreshMainDocument($mainDocument3);
    }
}
