<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\InvestigationReport\Uploads\MainDocument\InvestigationReportUploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class InvestigationReportUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $repository = Mockery::mock(InvestigationReportRepository::class);
        $uploadProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadProcessor->expects('process')->with($request, $repository, InvestigationReportMainDocument::class);
        $operation = Mockery::mock(Operation::class);
        $processor = new InvestigationReportUploadMainDocumentProcessor($repository, $uploadProcessor);
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $repository = Mockery::mock(InvestigationReportRepository::class);
        $uploadProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $operation = Mockery::mock(Operation::class);
        $processor = new InvestigationReportUploadMainDocumentProcessor($repository, $uploadProcessor);
        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
