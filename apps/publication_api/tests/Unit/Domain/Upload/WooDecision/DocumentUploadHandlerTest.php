<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Upload\WooDecision;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\Upload\WooDecision\DocumentUploadHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class DocumentUploadHandlerTest extends UnitTestCase
{
    private WooDecisionDispatcher&MockInterface $dispatcher;
    private DocumentUploadHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = Mockery::mock(WooDecisionDispatcher::class);

        $this->handler = new DocumentUploadHandler($this->dispatcher);
    }

    public function testSkipsUploadsForOtherGroup(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::MAIN_DOCUMENTS);

        $this->handler->onUploadValidated(new UploadValidatedEvent($uploadEntity));
    }

    public function testHandleUploadSuccessfully(): void
    {
        $wooDecisionId = Uuid::v6();
        $uploadEntityId = Uuid::v6();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getId')->andReturn($uploadEntityId);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::API_WOO_DECISION_DOCUMENTS);
        $uploadEntity->expects('getContext->getString')->with('dossierId')->andReturn($wooDecisionId);

        $this->dispatcher
            ->expects('dispatchProcessUploadedDocumentsCommand')
            ->withArgs(static function (Uuid $wooDecisionIdArg, Uuid $uploadEntityIdArg) use ($wooDecisionId, $uploadEntityId) {
                return $wooDecisionIdArg->equals($wooDecisionId) && $uploadEntityIdArg->equals($uploadEntityId);
            })
            ->andReturnNull();

        $this->handler->onUploadValidated(new UploadValidatedEvent($uploadEntity));
    }
}
