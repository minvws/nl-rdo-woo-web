<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment\Handler;

use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\AttachmentDeleter;
use Shared\Domain\Publication\Attachment\AttachmentDispatcher;
use Shared\Domain\Publication\Attachment\Command\WithDrawAttachmentCommand;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Shared\Domain\Publication\Attachment\Exception\AttachmentWithdrawException;
use Shared\Domain\Publication\Attachment\Handler\AttachmentEntityLoader;
use Shared\Domain\Publication\Attachment\Handler\WithdrawAttachmentHandler;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class WithdrawAttachmentHandlerTest extends UnitTestCase
{
    private AttachmentRepository&MockInterface $attachmentRepository;
    private AttachmentDispatcher&MockInterface $attachmentDispatcher;
    private AttachmentEntityLoader&MockInterface $entityLoader;
    private WithdrawAttachmentHandler $handler;
    private AttachmentDeleter&MockInterface $deleter;

    protected function setUp(): void
    {
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);
        $this->attachmentDispatcher = \Mockery::mock(AttachmentDispatcher::class);
        $this->entityLoader = \Mockery::mock(AttachmentEntityLoader::class);
        $this->deleter = \Mockery::mock(AttachmentDeleter::class);

        $this->handler = new WithdrawAttachmentHandler(
            $this->attachmentRepository,
            $this->entityLoader,
            $this->attachmentDispatcher,
            $this->deleter,
        );

        parent::setUp();
    }

    public function testWithdrawSuccessful(): void
    {
        $dossierUuid = Uuid::v6();

        $reason = AttachmentWithdrawReason::UNRELATED;
        $explanation = 'foo bar';

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);
        $attachment->shouldReceive('canWithdraw')->andReturnTrue();
        $attachment->expects('withdraw')->with($reason, $explanation);

        $this->entityLoader
            ->expects('loadAndValidateAttachment')
            ->with($dossierUuid, $attachmentId, DossierStatusTransition::UPDATE_ATTACHMENT)
            ->andReturn($attachment);

        $this->deleter->expects('delete')->with($attachment);

        $this->attachmentRepository->expects('save')->with($attachment, true);

        $this->attachmentDispatcher->expects('dispatchAttachmentWithdrawnEvent')->with($attachment);

        $this->handler->__invoke(
            new WithDrawAttachmentCommand($dossierUuid, $attachmentId, $reason, $explanation),
        );
    }

    public function testHandlerThrowsExceptionWhenAttachmentCannotBeWithdrawn(): void
    {
        $dossierUuid = Uuid::v6();

        $reason = AttachmentWithdrawReason::UNRELATED;
        $explanation = 'foo bar';

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);
        $attachment->shouldReceive('canWithdraw')->andReturnFalse();

        $this->entityLoader
            ->expects('loadAndValidateAttachment')
            ->with($dossierUuid, $attachmentId, DossierStatusTransition::UPDATE_ATTACHMENT)
            ->andReturn($attachment);

        $this->expectException(AttachmentWithdrawException::class);

        $this->handler->__invoke(
            new WithDrawAttachmentCommand($dossierUuid, $attachmentId, $reason, $explanation),
        );
    }
}
