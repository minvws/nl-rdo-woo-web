<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Validator;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteAttachments;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteAttachmentsValidator;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NoIncompleteAttachmentsValidatorTest extends UnitTestCase
{
    public function testValidateThrowsExceptionForUnsupportedConstraint(): void
    {
        /** @var AttachmentRepository&MockInterface $repository */
        $repository = Mockery::mock(AttachmentRepository::class);

        $validator = new NoIncompleteAttachmentsValidator($repository);

        $this->expectException(UnexpectedTypeException::class);
        $validator->validate('foo', new NotNull());
    }

    public function testValidateThrowsExceptionForInvalidValue(): void
    {
        /** @var AttachmentRepository&MockInterface $repository */
        $repository = Mockery::mock(AttachmentRepository::class);

        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);

        $validator = new NoIncompleteAttachmentsValidator($repository);
        $validator->initialize($context);

        $this->expectException(UnexpectedValueException::class);
        $validator->validate(new stdClass(), new NoIncompleteAttachments());
    }

    public function testValidateAddsViolationWhenIncompleteAttachmentsExist(): void
    {
        $dossierId = Uuid::v6();

        /** @var AbstractDossier&MockInterface $dossier */
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId')->andReturn($dossierId);

        /** @var AttachmentRepository&MockInterface $repository */
        $repository = Mockery::mock(AttachmentRepository::class);
        $repository->expects('hasIncompleteAttachmentsForDossier')->with($dossierId)->andReturnTrue();

        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation->addViolation');

        $validator = new NoIncompleteAttachmentsValidator($repository);
        $validator->initialize($context);

        $validator->validate($dossier, new NoIncompleteAttachments());
    }

    public function testValidateAddsNoViolationWhenNoIncompleteAttachmentsExist(): void
    {
        $dossierId = Uuid::v6();

        /** @var AbstractDossier&MockInterface $dossier */
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId')->andReturn($dossierId);

        /** @var AttachmentRepository&MockInterface $repository */
        $repository = Mockery::mock(AttachmentRepository::class);
        $repository->expects('hasIncompleteAttachmentsForDossier')->with($dossierId)->andReturnFalse();

        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new NoIncompleteAttachmentsValidator($repository);
        $validator->initialize($context);

        $validator->validate($dossier, new NoIncompleteAttachments());
    }
}
