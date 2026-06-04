<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\RequestForAdvice\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\RequestForAdvice\Uploads\Attachment\RequestForAdviceUploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class RequestForAdviceUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $requestForAdviceRepository = Mockery::mock(RequestForAdviceRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadAttachmentProcessor->expects('process')->with($request, $requestForAdviceRepository, RequestForAdviceAttachment::class);

        $processor = new RequestForAdviceUploadAttachmentProcessor(
            $requestForAdviceRepository,
            $uploadAttachmentProcessor,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $requestForAdviceRepository = Mockery::mock(RequestForAdviceRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);

        $processor = new RequestForAdviceUploadAttachmentProcessor(
            $requestForAdviceRepository,
            $uploadAttachmentProcessor,
        );

        $this->expectException(ValidationException::class);

        $processor->process(new stdClass(), $operation);
    }
}
