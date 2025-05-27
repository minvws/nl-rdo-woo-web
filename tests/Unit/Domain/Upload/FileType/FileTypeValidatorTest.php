<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileTypeValidator;
use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class FileTypeValidatorTest extends MockeryTestCase
{
    private LoggerInterface&MockInterface $logger;
    private MimeTypeHelper&MockInterface $mimeTypeHelper;
    private FileTypeValidator $validator;

    public function setUp(): void
    {
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->mimeTypeHelper = \Mockery::mock(MimeTypeHelper::class);

        $this->validator = new FileTypeValidator(
            $this->mimeTypeHelper,
            $this->logger,
        );

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

    public function testValidateThrowsErrorWhenMimeTypeIsNotValid(): void
    {
        $upload = \Mockery::mock(FilesystemFile::class);

        $request = new Request(
            request: ['groupId' => UploadGroupId::MAIN_DOCUMENTS->value],
        );

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->shouldReceive('getFile')->andReturn($upload);

        $this->mimeTypeHelper
            ->expects('detectMimeType')
            ->with($upload)
            ->andReturn('foo/bar');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('foo/bar', UploadGroupId::MAIN_DOCUMENTS)
            ->andReturnFalse();

        $this->logger->expects('error');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(FileTypeValidator::ERROR_WHITELIST);

        $this->validator->onValidate($event);
    }

    public function testValidateAcceptsValidMimeType(): void
    {
        $upload = \Mockery::mock(FilesystemFile::class);

        $request = new Request(
            request: ['groupId' => UploadGroupId::MAIN_DOCUMENTS->value],
        );

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->shouldReceive('getFile')->andReturn($upload);

        $this->mimeTypeHelper
            ->expects('detectMimeType')
            ->with($upload)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('application/pdf', UploadGroupId::MAIN_DOCUMENTS)
            ->andReturnTrue();

        $this->logger->shouldNotHaveReceived('error');
        $this->validator->onValidate($event);
    }
}
