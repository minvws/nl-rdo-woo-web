<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Upload\WooDecision;

use Mockery;
use PublicationApi\Domain\Upload\WooDecision\DocumentUploadHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class DocumentUploadHandlerTest extends UnitTestCase
{
    public function testSkipsUploadsForOtherGroup(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::MAIN_DOCUMENTS);

        $documentUploadHandler = new DocumentUploadHandler(
            Mockery::mock(WooDecisionDispatcher::class),
            Mockery::mock(UploadContextReader::class),
        );
        $documentUploadHandler->__invoke(new UploadValidatedEvent($uploadEntity));
    }

    public function testHandleUploadSuccessfully(): void
    {
        $wooDecisionId = Uuid::v6();
        $uploadEntityId = Uuid::v6();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getId')->andReturn($uploadEntityId);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::API_WOO_DECISION_DOCUMENTS);

        $uploadContextReader = Mockery::mock(UploadContextReader::class);
        $uploadContextReader->expects('getDossierId')->with($uploadEntity)->andReturn($wooDecisionId);

        $wooDecisionDispatcher = Mockery::mock(WooDecisionDispatcher::class);
        $wooDecisionDispatcher
            ->expects('dispatchProcessUploadedDocumentsCommand')
            ->withArgs(static function (Uuid $wooDecisionIdArg, Uuid $uploadEntityIdArg) use ($wooDecisionId, $uploadEntityId) {
                return $wooDecisionIdArg->equals($wooDecisionId) && $uploadEntityIdArg->equals($uploadEntityId);
            })
            ->andReturnNull();

        $documentUploadHandler = new DocumentUploadHandler($wooDecisionDispatcher, $uploadContextReader);
        $documentUploadHandler->__invoke(new UploadValidatedEvent($uploadEntity));
    }
}
