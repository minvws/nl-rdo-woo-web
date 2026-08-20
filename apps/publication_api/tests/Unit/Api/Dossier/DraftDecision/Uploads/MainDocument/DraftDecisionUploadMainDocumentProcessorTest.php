<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument\DraftDecisionUploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class DraftDecisionUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $draftDecisionRepository = Mockery::mock(DraftDecisionRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')->with($request, $draftDecisionRepository, DraftDecisionMainDocument::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new DraftDecisionUploadMainDocumentProcessor(
            $draftDecisionRepository,
            $uploadMainDocumentProcessor,
        );
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $draftDecisionRepository = Mockery::mock(DraftDecisionRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new DraftDecisionUploadMainDocumentProcessor(
            $draftDecisionRepository,
            $uploadMainDocumentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
