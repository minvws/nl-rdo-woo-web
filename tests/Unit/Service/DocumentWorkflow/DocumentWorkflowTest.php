<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DocumentWorkflow;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Service\DocumentService;
use App\Service\DocumentWorkflow\DocumentWorkflow;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentWorkflowTest extends MockeryTestCase
{
    private DocumentService&MockInterface $documentService;
    private DocumentWorkflow $documentWorkflow;

    public function setUp(): void
    {
        $this->documentService = \Mockery::mock(DocumentService::class);

        $this->documentWorkflow = new DocumentWorkflow(
            $this->documentService,
        );
    }

    public function testGetStatus(): void
    {
        $document = \Mockery::mock(Document::class);

        $status = $this->documentWorkflow->getStatus($document);

        self::assertSame($document, $status->getDocument());
    }

    public function testReplaceThrowsExceptionWhenDocumentCannotBeReplaced(): void
    {
        $file = \Mockery::mock(UploadedFile::class);
        $dossier = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $document->expects('shouldBeUploaded')->andReturnFalse();

        $this->expectException(\RuntimeException::class);

        $this->documentWorkflow->replace($dossier, $document, $file);
    }

    public function testReplaceRepublishedWithdrawnDocument(): void
    {
        $file = \Mockery::mock(UploadedFile::class);
        $dossier = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $document->expects('shouldBeUploaded')->andReturnTrue();
        $document->expects('isWithdrawn')->andReturnTrue();

        $this->documentService->expects('replace')->with($dossier, $document, $file);
        $this->documentService->expects('republish')->with($document);

        $this->documentWorkflow->replace($dossier, $document, $file);
    }
}
