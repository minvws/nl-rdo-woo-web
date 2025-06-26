<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Upload\Process\EntityUploadStorer;
use App\Domain\Upload\WooDecision\DocumentUploadHandler;
use App\Domain\Uploader\Event\UploadValidatedEvent;
use App\Domain\Uploader\UploadEntity;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class DocumentUploadHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private EntityUploadStorer&MockInterface $uploadStorer;
    private DocumentFileService&MockInterface $documentFileService;
    private DocumentUploadHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->uploadStorer = \Mockery::mock(EntityUploadStorer::class);
        $this->documentFileService = \Mockery::mock(DocumentFileService::class);

        $this->handler = new DocumentUploadHandler(
            $this->wooDecisionRepository,
            $this->documentFileService,
            $this->uploadStorer,
        );
    }

    public function testSkipsUploadsForOtherGroup(): void
    {
        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getUploadGroupId')->andReturn(UploadGroupId::MAIN_DOCUMENTS);

        $this->handler->onUploadValidated(new UploadValidatedEvent($uploadEntity));
    }

    public function testHandleUploadSuccessfully(): void
    {
        $dossierId = Uuid::v6();

        $uploadEntity = \Mockery::mock(UploadEntity::class);
        $uploadEntity->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::WOO_DECISION_DOCUMENTS);
        $uploadEntity->shouldReceive('getContext->getString')->with('dossierId')->andReturn($dossierId->toRfc4122());
        $uploadEntity->shouldReceive('getFilename')->andReturn($filename = 'foo.bar');
        $uploadEntity->shouldReceive('getSize')->andReturn($size = 123);
        $uploadEntity->shouldReceive('getMimetype')->andReturn($mimetype = 'foo/bar');

        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->wooDecisionRepository->expects('findOneByDossierId')->with(\Mockery::on(
            static function (Uuid $id) use ($dossierId) {
                return $dossierId->toRfc4122() === $id->toRfc4122();
            }
        ))->andReturn($wooDecision);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $this->documentFileService->expects('getDocumentFileSet')->with($wooDecision)->andReturn($documentFileSet);

        $documentFileUpload = \Mockery::mock(DocumentFileUpload::class);
        $this->documentFileService->expects('createNewUpload')->with($documentFileSet, $filename)->andReturn($documentFileUpload);

        $this->uploadStorer->expects('storeUploadForEntity')->with($uploadEntity, $documentFileUpload);

        $this->documentFileService->expects('finishUpload')->with($documentFileSet, $documentFileUpload);

        $this->handler->onUploadValidated(new UploadValidatedEvent($uploadEntity));
    }
}
