<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\RequestForAdvice\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\RequestForAdvice\Uploads\MainDocument\RequestForAdviceUploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class RequestForAdviceUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $requestForAdviceRepository = Mockery::mock(RequestForAdviceRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')->with($request, $requestForAdviceRepository, RequestForAdviceMainDocument::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new RequestForAdviceUploadMainDocumentProcessor(
            $requestForAdviceRepository,
            $uploadMainDocumentProcessor,
        );
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $requestForAdviceRepository = Mockery::mock(RequestForAdviceRepository::class);

        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);

        $operation = Mockery::mock(Operation::class);

        $processor = new RequestForAdviceUploadMainDocumentProcessor(
            $requestForAdviceRepository,
            $uploadMainDocumentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
