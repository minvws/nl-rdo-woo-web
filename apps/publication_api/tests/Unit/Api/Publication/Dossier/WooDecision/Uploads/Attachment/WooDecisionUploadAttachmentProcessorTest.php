<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\WooDecision\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Attachment\WooDecisionUploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class WooDecisionUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadAttachmentProcessor->expects('process')->with($request, $wooDecisionRepository, WooDecisionAttachment::class);

        $processor = new WooDecisionUploadAttachmentProcessor(
            $uploadAttachmentProcessor,
            $wooDecisionRepository,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);

        $processor = new WooDecisionUploadAttachmentProcessor(
            $uploadAttachmentProcessor,
            $wooDecisionRepository,
        );

        $this->expectException(ValidationException::class);

        $processor->process(new stdClass(), $operation);
    }
}
