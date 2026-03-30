<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Service\AttachmentService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AttachmentServiceTest extends UnitTestCase
{
    public function testValidate(): void
    {
        $attachments = [
            Mockery::mock(DispositionAttachment::class),
            Mockery::mock(DispositionAttachment::class),
        ];

        $constraintViolationList = Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(0);

        $entityManager = Mockery::mock(EntityManagerInterface::class);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($attachments)
            ->andReturn($constraintViolationList);

        $attachmentService = new AttachmentService($entityManager, $validator);
        $attachmentService->validate($attachments);
    }

    public function testValidateWithErrors(): void
    {
        $attachments = [
            Mockery::mock(DispositionAttachment::class),
            Mockery::mock(DispositionAttachment::class),
        ];

        $constraintViolationList = Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(1);

        $entityManager = Mockery::mock(EntityManagerInterface::class);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($attachments)
            ->andReturn($constraintViolationList);

        $attachmentService = new AttachmentService($entityManager, $validator);

        $this->expectException(ValidationFailedException::class);
        $attachmentService->validate($attachments);
    }

    public function testRefreshAttachments(): void
    {
        $attachment1 = Mockery::mock(DispositionAttachment::class);
        $attachment2 = Mockery::mock(DispositionAttachment::class);
        $attachment3 = Mockery::mock(DispositionAttachment::class);

        $attachments = [
            $attachment1,
            $attachment2,
            $attachment3,
        ];

        $unitOfWork = Mockery::mock(UnitOfWork::class);
        $unitOfWork->expects('isScheduledForInsert')
            ->with($attachment2)
            ->andReturnTrue();
        $unitOfWork->expects('isScheduledForInsert')
            ->with($attachment3)
            ->andReturnFalse();

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('getUnitOfWork')
            ->andReturn($unitOfWork);
        $entityManager->expects('contains')
            ->with($attachment1)
            ->andReturnFalse();
        $entityManager->expects('contains')
            ->with($attachment2)
            ->andReturnTrue();
        $entityManager->expects('contains')
            ->with($attachment3)
            ->andReturnTrue();
        $entityManager->expects('refresh')
            ->with($attachment3);

        $validator = Mockery::mock(ValidatorInterface::class);

        $attachmentService = new AttachmentService($entityManager, $validator);
        $attachmentService->refreshAttachments($attachments);
    }
}
