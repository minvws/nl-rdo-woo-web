<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment\AnnualReportUploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class AnnualReportUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $annualReportRepository = Mockery::mock(AnnualReportRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadAttachmentProcessor->expects('process')->with($request, $annualReportRepository, AnnualReportAttachment::class);

        $processor = new AnnualReportUploadAttachmentProcessor(
            $annualReportRepository,
            $uploadAttachmentProcessor,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $annualReportRepository = Mockery::mock(AnnualReportRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);

        $processor = new AnnualReportUploadAttachmentProcessor(
            $annualReportRepository,
            $uploadAttachmentProcessor,
        );

        $this->expectException(ValidationException::class);

        $processor->process(new stdClass(), $operation);
    }
}
