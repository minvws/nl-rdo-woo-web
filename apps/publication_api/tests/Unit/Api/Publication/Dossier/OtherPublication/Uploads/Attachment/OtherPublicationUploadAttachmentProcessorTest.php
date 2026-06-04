<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\OtherPublication\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\OtherPublication\Uploads\Attachment\OtherPublicationUploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class OtherPublicationUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $otherPublicationRepository = Mockery::mock(OtherPublicationRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadAttachmentProcessor->expects('process')->with($request, $otherPublicationRepository, OtherPublicationAttachment::class);

        $processor = new OtherPublicationUploadAttachmentProcessor(
            $otherPublicationRepository,
            $uploadAttachmentProcessor,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $otherPublicationRepository = Mockery::mock(OtherPublicationRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);

        $processor = new OtherPublicationUploadAttachmentProcessor(
            $otherPublicationRepository,
            $uploadAttachmentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
