<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\OtherPublication\Uploads\MainDocument\OtherPublicationUploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class OtherPublicationUploadMainDocumentProcessorTest extends UnitTestCase
{
    public function testProcessDelegatesToUploadMainDocumentProcessor(): void
    {
        $request = Mockery::mock(UploadMainDocumentRequestInterface::class);
        $otherPublicationRepository = Mockery::mock(OtherPublicationRepository::class);
        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $uploadMainDocumentProcessor->expects('process')->with($request, $otherPublicationRepository, OtherPublicationMainDocument::class);
        $operation = Mockery::mock(Operation::class);

        $processor = new OtherPublicationUploadMainDocumentProcessor(
            $otherPublicationRepository,
            $uploadMainDocumentProcessor,
        );
        $processor->process($request, $operation);
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $otherPublicationRepository = Mockery::mock(OtherPublicationRepository::class);
        $uploadMainDocumentProcessor = Mockery::mock(UploadMainDocumentProcessor::class);
        $operation = Mockery::mock(Operation::class);

        $processor = new OtherPublicationUploadMainDocumentProcessor(
            $otherPublicationRepository,
            $uploadMainDocumentProcessor,
        );

        $this->expectException(ValidationException::class);
        $processor->process(new stdClass(), $operation);
    }
}
