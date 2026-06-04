<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\Disposition\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\Disposition\Uploads\MainDocument\DispositionUploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class DispositionUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $dispositionRepository = Mockery::mock(DispositionRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')->with($request, $dispositionRepository, DispositionMainDocument::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new DispositionUploadMainDocumentProcessor(
            $dispositionRepository,
            $uploadMainDocumentProcessor,
        );
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $dispositionRepository = Mockery::mock(DispositionRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new DispositionUploadMainDocumentProcessor(
            $dispositionRepository,
            $uploadMainDocumentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
