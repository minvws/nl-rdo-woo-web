<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication;

use ApiPlatform\Validator\Exception\ValidationException;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Uploads\Document\DocumentUploadProcessor;
use PublicationApi\Domain\Upload\DocumentUploadStatusService;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Domain\Upload\UploadValidationService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\FileHashService;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Symfony\Component\Uid\UuidV6;
use Symfony\Component\Validator\ConstraintViolation;

class DocumentUploadProcessorTest extends UnitTestCase
{
    private DocumentUploadStatusService&MockInterface $documentUploadStatusService;
    private UploadValidationService&MockInterface $uploadValidationService;
    private UploadService&MockInterface $uploadService;
    private DocumentUploadProcessor $documentUploadProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentUploadStatusService = Mockery::mock(DocumentUploadStatusService::class);
        $this->uploadValidationService = Mockery::mock(UploadValidationService::class);
        $this->uploadService = Mockery::mock(UploadService::class);

        $this->documentUploadProcessor = new DocumentUploadProcessor(
            $this->documentUploadStatusService,
            $this->uploadValidationService,
            $this->uploadService,
        );
    }

    public function testProcessWhenDocumentWasNotUploaded(): void
    {
        $testContent = 'test content';
        $stream = Utils::streamFor($testContent);

        $wooDecisionId = new UuidV6();
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn($wooDecisionId);

        $documentFileInfoName = 'foobar.pdf';
        $documentDocumentId = DocumentId::create('1337');
        $expectedFileName = '1337.pdf';
        $documentId = new UuidV6();
        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($documentId);
        $document->expects('getFileInfo->getHash')->andReturnNull();
        $document->expects('getFileInfo->getName')->andReturn($documentFileInfoName);
        $document->expects('getDocumentId')->andReturn($documentDocumentId);

        $this->uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $this->uploadService
            ->expects('handleUpload')
            ->with(Mockery::on(static function (StreamUpload $streamUpload) use ($stream, $wooDecisionId, $documentId, $expectedFileName): bool {
                if ($streamUpload->fileName !== $expectedFileName) {
                    return false;
                }

                if ($streamUpload->stream !== $stream) {
                    return false;
                }

                if ($streamUpload->groupId !== UploadGroupId::API_WOO_DECISION_DOCUMENTS) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get('dossierId') !== $wooDecisionId->toRfc4122()) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get('documentId') !== $documentId->toRfc4122()) {
                    return false;
                }

                return true;
            }));

        $this->documentUploadProcessor->process($wooDecision, $document, $stream);
    }

    public function testProcessWhenDocumentWithOtherHashExists(): void
    {
        $testContent = 'test content';
        $stream = Utils::streamFor($testContent);

        $wooDecisionId = new UuidV6();
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn($wooDecisionId);

        $documentFileInfoName = 'foobar.pdf';
        $documentDocumentId = DocumentId::create('1337');
        $expectedFileName = '1337.pdf';
        $documentId = new UuidV6();
        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($documentId);
        $document->expects('getFileInfo->getHash')->andReturn(new UuidV6()->toRfc4122()); // random hash to ensure the upload is processed
        $document->expects('getFileInfo->getName')->andReturn($documentFileInfoName);
        $document->expects('getDocumentId')->andReturn($documentDocumentId);

        $this->documentUploadStatusService->expects('getUploadStatus')->andReturn(UploadStatus::PROCESSED);

        $this->uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $this->uploadService
            ->expects('handleUpload')
            ->with(Mockery::on(static function (StreamUpload $streamUpload) use ($stream, $wooDecisionId, $documentId, $expectedFileName): bool {
                if ($streamUpload->fileName !== $expectedFileName) {
                    return false;
                }

                if ($streamUpload->stream !== $stream) {
                    return false;
                }

                if ($streamUpload->groupId !== UploadGroupId::API_WOO_DECISION_DOCUMENTS) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get('dossierId') !== $wooDecisionId->toRfc4122()) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get('documentId') !== $documentId->toRfc4122()) {
                    return false;
                }

                return true;
            }));

        $this->documentUploadProcessor->process($wooDecision, $document, $stream);
    }

    public function testProcessWhenDocumentWithOtherHashExistsButNotYetProcessed(): void
    {
        $testContent = 'test content';
        $stream = Utils::streamFor($testContent);

        $wooDecisionId = new UuidV6();
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn($wooDecisionId);

        $documentFileInfoName = 'foobar.pdf';
        $documentDocumentId = DocumentId::create('1337');
        $expectedFileName = '1337.pdf';
        $documentId = new UuidV6();
        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($documentId);
        $document->expects('getFileInfo->getHash')->andReturn(new UuidV6()->toRfc4122()); // random hash to ensure the upload is processed
        $document->expects('getFileInfo->getName')->andReturn($documentFileInfoName);
        $document->expects('getDocumentId')->andReturn($documentDocumentId);

        $this->documentUploadStatusService->expects('getUploadStatus')->andReturn(UploadStatus::UPLOAD_REQUIRED);

        $this->uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $this->uploadService
            ->expects('handleUpload')
            ->with(Mockery::on(static function (StreamUpload $streamUpload) use ($stream, $wooDecisionId, $documentId, $expectedFileName): bool {
                if ($streamUpload->fileName !== $expectedFileName) {
                    return false;
                }

                if ($streamUpload->stream !== $stream) {
                    return false;
                }

                if ($streamUpload->groupId !== UploadGroupId::API_WOO_DECISION_DOCUMENTS) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get('dossierId') !== $wooDecisionId->toRfc4122()) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get('documentId') !== $documentId->toRfc4122()) {
                    return false;
                }

                return true;
            }));

        $this->documentUploadProcessor->process($wooDecision, $document, $stream);
    }

    public function testProcessWhenDocumentWithSameHashExistsAndProcessed(): void
    {
        $testContent = 'test content';
        $stream = Utils::streamFor($testContent);
        $streamHash = FileHashService::calculatePsrStreamHash($stream);

        $wooDecision = Mockery::mock(WooDecision::class);

        $document = Mockery::mock(Document::class);
        $document->expects('getFileInfo->getHash')->andReturn($streamHash);

        $this->documentUploadStatusService->expects('getUploadStatus')->andReturn(UploadStatus::PROCESSED);

        $this->uploadService
            ->expects('handleUpload')
            ->never();

        $this->documentUploadProcessor->process($wooDecision, $document, $stream);
    }

    public function testProcessThrowsValidationExceptionWhenUploadValidationFails(): void
    {
        $stream = Utils::streamFor('test content');

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getId')->andReturn(new UuidV6());

        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn(new UuidV6());
        $document->expects('getFileInfo->getHash')->andReturnNull();
        $document->expects('getFileInfo->getName')->andReturn('foobar.pdf');
        $document->expects('getDocumentId')->andReturn(DocumentId::create('1337'));

        $violation = new ConstraintViolation('Validation failed', '', [], null, '', null);
        $this->uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([$violation]);

        $this->uploadService->expects('handleUpload');

        $this->expectException(ValidationException::class);

        $this->documentUploadProcessor->process($wooDecision, $document, $stream);
    }
}
