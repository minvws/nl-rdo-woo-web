<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\InvestigationReport\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\InvestigationReport\Uploads\Attachment\InvestigationReportUploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class InvestigationReportUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $repository = Mockery::mock(InvestigationReportRepository::class);
        $uploadProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadProcessor->expects('process')->with($request, $repository, InvestigationReportAttachment::class);
        $operation = Mockery::mock(Operation::class);
        $processor = new InvestigationReportUploadAttachmentProcessor($repository, $uploadProcessor);
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $repository = Mockery::mock(InvestigationReportRepository::class);
        $uploadProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $operation = Mockery::mock(Operation::class);
        $processor = new InvestigationReportUploadAttachmentProcessor($repository, $uploadProcessor);
        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
