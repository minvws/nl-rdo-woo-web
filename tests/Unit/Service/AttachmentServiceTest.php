<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Service\AttachmentService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function array_column;

class AttachmentServiceTest extends UnitTestCase
{
    private AttachmentService $attachmentService;
    private ValidatorInterface&MockInterface $validatorInterface;

    protected function setUp(): void
    {
        $this->validatorInterface = Mockery::mock(ValidatorInterface::class);
        $this->attachmentService = new AttachmentService($this->validatorInterface);

        parent::setUp();
    }

    public function testValidate(): void
    {
        $attachments = [
            Mockery::mock(DispositionAttachment::class),
            Mockery::mock(DispositionAttachment::class),
        ];

        $constraintViolationList = Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(1);

        $this->validatorInterface->expects('validate')
            ->with($attachments, null, array_column(DossierValidationGroup::cases(), 'value'))
            ->andReturn($constraintViolationList);

        $this->expectException(ValidationFailedException::class);
        $this->attachmentService->validate($attachments);
    }
}
