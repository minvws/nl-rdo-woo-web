<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileTypeValidator;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class FileTypeValidatorTest extends MockeryTestCase
{
    private LoggerInterface&MockInterface $logger;
    private FileTypeValidator $validator;

    public function setUp(): void
    {
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->validator = new FileTypeValidator($this->logger);

        parent::setUp();
    }

    public function testValidateThrowsExceptionWhenGroupCannotBeDetermined(): void
    {
        $request = new Request(request: ['groupId' => 'non.existent']);

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);

        $this->logger->expects('error');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(FileTypeValidator::ERROR_TECHNICAL);

        $this->validator->onValidate($event);
    }

    public function testValidateThrowsErrorWhenMimeTypeIsNotAllowed(): void
    {
        $request = new Request(request: ['groupId' => UploadGroupId::MAIN_DOCUMENTS->value]);

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->shouldReceive('getFile->getMimetype')->andReturn('foo/bar');

        $this->logger->expects('error');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(FileTypeValidator::ERROR_WHITELIST);

        $this->validator->onValidate($event);
    }

    public function testValidateContinuesWithoutExceptionForWhitelistedMimeType(): void
    {
        $request = new Request(request: ['groupId' => UploadGroupId::MAIN_DOCUMENTS->value]);

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->expects('getFile->getMimetype')->andReturn('application/pdf');

        $this->validator->onValidate($event);
    }

    public function testValidateFallsBackToExtensionMimetypeAndThrowsExceptionIfThisAlsoFails(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);
        $upload->expects('getClientOriginalName')->andReturn('foo.zip');

        $request = new Request(
            request: ['groupId' => UploadGroupId::MAIN_DOCUMENTS->value],
            files: [
                'file' => $upload,
            ],
        );

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->shouldReceive('getFile->getMimetype')->andReturn('foo/bar');

        $this->logger->expects('error');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(FileTypeValidator::ERROR_WHITELIST);

        $this->validator->onValidate($event);
    }

    public function testValidateFallsBackToExtensionMimetypeAndCanAcceptThis(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);
        $upload->expects('getClientOriginalName')->andReturn('foo.docx');

        $request = new Request(
            request: ['groupId' => UploadGroupId::MAIN_DOCUMENTS->value],
            files: [
                'file' => $upload,
            ],
        );

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->shouldReceive('getFile->getMimetype')->andReturn('foo/bar');

        $this->validator->onValidate($event);
    }
}
