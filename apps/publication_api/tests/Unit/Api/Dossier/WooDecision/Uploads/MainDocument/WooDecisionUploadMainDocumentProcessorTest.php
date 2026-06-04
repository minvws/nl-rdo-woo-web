<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\WooDecision\Uploads\MainDocument\WooDecisionUploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class WooDecisionUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $operation = Mockery::mock(Operation::class);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')->with($request, $wooDecisionRepository, WooDecisionMainDocument::class);

        $processor = new WooDecisionUploadMainDocumentProcessor(
            $uploadMainDocumentProcessor,
            $wooDecisionRepository,
        );

        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $operation = Mockery::mock(Operation::class);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);

        $processor = new WooDecisionUploadMainDocumentProcessor(
            $uploadMainDocumentProcessor,
            $wooDecisionRepository,
        );

        $this->expectException(ValidationException::class);

        $processor->process(new stdClass(), $operation);
    }
}
