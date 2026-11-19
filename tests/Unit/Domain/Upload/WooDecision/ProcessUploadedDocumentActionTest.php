<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\WooDecision;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\WooDecision\ProcessUploadedDocumentAction;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class ProcessUploadedDocumentActionTest extends UnitTestCase
{
    public function testProcess(): void
    {
        $dossierId = Uuid::v6();
        $filename = 'foo.bar';

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getFilename')->andReturn($filename);

        $uploadContextReader = Mockery::mock(UploadContextReader::class);
        $uploadContextReader->expects('getDossierId')->with($uploadEntity)->andReturn($dossierId);

        $wooDecision = Mockery::mock(WooDecision::class);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($wooDecision);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileService = Mockery::mock(DocumentFileService::class);
        $documentFileService
            ->expects('getDocumentFileSet')
            ->with($wooDecision)
            ->andReturn($documentFileSet);

        $documentFileUpload = Mockery::mock(DocumentFileUpload::class);
        $documentFileService
            ->expects('createNewUpload')
            ->with($documentFileSet, $filename)
            ->andReturn($documentFileUpload);

        $entityUploadStorer = Mockery::mock(EntityUploadStorer::class);
        $entityUploadStorer
            ->expects('storeUploadForEntity')
            ->with($uploadEntity, $documentFileUpload);

        $documentFileService
            ->expects('finishUpload')
            ->with($documentFileSet, $documentFileUpload);

        $processUploadedDocumentAction = new ProcessUploadedDocumentAction(
            $wooDecisionRepository,
            $documentFileService,
            $entityUploadStorer,
            $uploadContextReader,
        );
        $processUploadedDocumentAction->execute($uploadEntity);
    }
}
