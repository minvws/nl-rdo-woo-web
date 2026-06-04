<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\ComplaintJudgement\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\ComplaintJudgement\Uploads\MainDocument\ComplaintJudgementUploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class ComplaintJudgementUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $complaintJudgementRepository = Mockery::mock(ComplaintJudgementRepository::class);
        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')
            ->with($request, $complaintJudgementRepository, ComplaintJudgementMainDocument::class);

        $processor = new ComplaintJudgementUploadMainDocumentProcessor(
            $complaintJudgementRepository,
            $uploadMainDocumentProcessor,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $complaintJudgementRepository = Mockery::mock(ComplaintJudgementRepository::class);
        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);

        $processor = new ComplaintJudgementUploadMainDocumentProcessor(
            $complaintJudgementRepository,
            $uploadMainDocumentProcessor,
        );

        $this->expectException(ValidationException::class);

        $processor->process(new stdClass(), $operation);
    }
}
