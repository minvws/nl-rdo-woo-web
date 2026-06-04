<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\WooDecision;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\WooDecision\ProcessUploadedDocumentAction;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class ProcessUploadedDocumentActionTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentFileService&MockInterface $documentFileService;
    private EntityUploadStorer&MockInterface $entityUploadStorer;
    private ProcessUploadedDocumentAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->documentFileService = Mockery::mock(DocumentFileService::class);
        $this->entityUploadStorer = Mockery::mock(EntityUploadStorer::class);

        $this->action = new ProcessUploadedDocumentAction(
            $this->wooDecisionRepository,
            $this->documentFileService,
            $this->entityUploadStorer,
        );
    }

    public function testProcess(): void
    {
        $dossierId = Uuid::v6();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getContext->getString')->with('dossierId')->andReturn($dossierId->toRfc4122());
        $uploadEntity->expects('getFilename')->andReturn($filename = 'foo.bar');

        $wooDecision = Mockery::mock(WooDecision::class);

        $this->wooDecisionRepository
            ->expects('findOneByDossierId')
            ->with(Mockery::on(static function (Uuid $id) use ($dossierId) {
                return $dossierId->toRfc4122() === $id->toRfc4122();
            }))
            ->andReturn($wooDecision);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $this->documentFileService
            ->expects('getDocumentFileSet')
            ->with($wooDecision)
            ->andReturn($documentFileSet);

        $documentFileUpload = Mockery::mock(DocumentFileUpload::class);
        $this->documentFileService
            ->expects('createNewUpload')
            ->with($documentFileSet, $filename)
            ->andReturn($documentFileUpload);

        $this->entityUploadStorer
            ->expects('storeUploadForEntity')
            ->with($uploadEntity, $documentFileUpload);

        $this->documentFileService
            ->expects('finishUpload')
            ->with($documentFileSet, $documentFileUpload);

        $this->action->execute($uploadEntity);
    }
}
