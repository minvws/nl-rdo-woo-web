<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment\DraftDecisionUploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class DraftDecisionUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $draftDecisionRepository = Mockery::mock(DraftDecisionRepository::class);

        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadAttachmentProcessor->expects('process')->with($request, $draftDecisionRepository, DraftDecisionAttachment::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new DraftDecisionUploadAttachmentProcessor(
            $draftDecisionRepository,
            $uploadAttachmentProcessor,
        );
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $draftDecisionRepository = Mockery::mock(DraftDecisionRepository::class);

        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new DraftDecisionUploadAttachmentProcessor(
            $draftDecisionRepository,
            $uploadAttachmentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
