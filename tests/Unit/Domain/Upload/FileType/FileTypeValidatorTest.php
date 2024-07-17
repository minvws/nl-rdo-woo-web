<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileTypeHelper;
use App\Domain\Upload\FileType\FileTypeValidator;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class FileTypeValidatorTest extends MockeryTestCase
{
    private LoggerInterface&MockInterface $logger;
    private FileTypeValidator $validator;
    private FileTypeHelper&MockInterface $fileTypeHelper;

    public function setUp(): void
    {
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->fileTypeHelper = \Mockery::mock(FileTypeHelper::class);

        $this->validator = new FileTypeValidator(
            $this->logger,
            $this->fileTypeHelper,
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

    public function testValidateThrowsErrorWhenMimeTypeIsNotAllowed(): void
    {
        $request = new Request(request: ['groupId' => UploadGroupId::COVENANT_DOCUMENTS->value]);

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->shouldReceive('getFile->getMimetype')->andReturn('foo/bar');

        $this->fileTypeHelper->expects('getMimeTypesByUploadGroup')->with(UploadGroupId::COVENANT_DOCUMENTS)->andReturn([
            'application/pdf',
        ]);

        $this->logger->expects('error');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(FileTypeValidator::ERROR_WHITELIST);

        $this->validator->onValidate($event);
    }

    public function testValidateContinuesWithoutExceptionForWhitelistedMimeType(): void
    {
        $request = new Request(request: ['groupId' => UploadGroupId::COVENANT_DOCUMENTS->value]);

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getRequest')->andReturn($request);
        $event->expects('getFile->getMimetype')->andReturn('application/pdf');

        $this->fileTypeHelper->expects('getMimeTypesByUploadGroup')->with(UploadGroupId::COVENANT_DOCUMENTS)->andReturn([
            'foo/bar',
            'application/pdf',
        ]);

        $this->validator->onValidate($event);
    }
}
