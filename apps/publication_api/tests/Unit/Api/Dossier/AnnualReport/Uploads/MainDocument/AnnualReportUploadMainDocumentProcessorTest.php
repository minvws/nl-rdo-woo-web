<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\MainDocument\AnnualReportUploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class AnnualReportUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $annualReportRepository = Mockery::mock(AnnualReportRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')->with($request, $annualReportRepository, AnnualReportMainDocument::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new AnnualReportUploadMainDocumentProcessor(
            $annualReportRepository,
            $uploadMainDocumentProcessor,
        );
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $annualReportRepository = Mockery::mock(AnnualReportRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new AnnualReportUploadMainDocumentProcessor(
            $annualReportRepository,
            $uploadMainDocumentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
