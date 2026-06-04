<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\Covenant\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\Covenant\Uploads\Attachment\CovenantUploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class CovenantUploadAttachmentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadAttachmentProcessor(): void
    {
        $request = Mockery::mock(UploadAttachmentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $covenantRepository = Mockery::mock(CovenantRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);
        $uploadAttachmentProcessor->expects('process')->with($request, $covenantRepository, CovenantAttachment::class);

        $processor = new CovenantUploadAttachmentProcessor(
            $covenantRepository,
            $uploadAttachmentProcessor,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $covenantRepository = Mockery::mock(CovenantRepository::class);
        $uploadAttachmentProcessor = Mockery::mock(UploadAttachmentProcessor::class);

        $processor = new CovenantUploadAttachmentProcessor(
            $covenantRepository,
            $uploadAttachmentProcessor,
        );

        $this->expectException(ValidationException::class);

        $processor->process(new stdClass(), $operation);
    }
}
