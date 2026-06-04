<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\Disposition\Uploads\Attachment\DispositionUploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class DispositionUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $dispositionRepository = Mockery::mock(DispositionRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadAttachmentProcessor->expects('process')->with($request, $dispositionRepository, DispositionAttachment::class);

        $processor = new DispositionUploadAttachmentProcessor(
            $dispositionRepository,
            $uploadAttachmentProcessor,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $dispositionRepository = Mockery::mock(DispositionRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);

        $processor = new DispositionUploadAttachmentProcessor(
            $dispositionRepository,
            $uploadAttachmentProcessor,
        );

        $this->expectException(ValidationException::class);

        $processor->process(new stdClass(), $operation);
    }
}
