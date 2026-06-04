<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\Advice\Uploads\MainDocument\AdviceUploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class AdviceUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $adviceRepository = Mockery::mock(AdviceRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')->with($request, $adviceRepository, AdviceMainDocument::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new AdviceUploadMainDocumentProcessor(
            $adviceRepository,
            $uploadMainDocumentProcessor,
        );
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $adviceRepository = Mockery::mock(AdviceRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new AdviceUploadMainDocumentProcessor(
            $adviceRepository,
            $uploadMainDocumentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
