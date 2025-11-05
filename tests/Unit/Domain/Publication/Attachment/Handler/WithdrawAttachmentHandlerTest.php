<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDeleter;
use App\Domain\Publication\Attachment\AttachmentDispatcher;
use App\Domain\Publication\Attachment\Command\WithDrawAttachmentCommand;
use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use App\Domain\Publication\Attachment\Exception\AttachmentWithdrawException;
use App\Domain\Publication\Attachment\Handler\AttachmentEntityLoader;
use App\Domain\Publication\Attachment\Handler\WithdrawAttachmentHandler;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class WithdrawAttachmentHandlerTest extends MockeryTestCase
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
