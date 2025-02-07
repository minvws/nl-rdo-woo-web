<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\AntiVirus;

use App\Domain\Upload\AntiVirus\ClamAvFileScanner;
use App\Domain\Upload\AntiVirus\ClamAvUploadValidator;
use App\Domain\Upload\AntiVirus\FileScanResult;
use App\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\Uploader\File\FileInterface;

final class ClamAvUploadValidatorTest extends MockeryTestCase
{
    private ClamAvFileScanner&MockInterface $scanner;
    private SevenZipFileStrategy&MockInterface $sevenZipStrategy;
    private ClamAvUploadValidator $validator;

    public function setUp(): void
    {
        $this->scanner = \Mockery::mock(ClamAvFileScanner::class);
        $this->sevenZipStrategy = \Mockery::mock(SevenZipFileStrategy::class);

        $this->validator = new ClamAvUploadValidator(
            $this->scanner,
            $this->sevenZipStrategy,
        );

        parent::setUp();
    }

    public function testValidateThrowsErrorWhenScannerReturnsSizeExceededAndFileIsNotAZipArchive(): void
    {
        $file = \Mockery::mock(FileInterface::class);
        $file->shouldReceive('getPathname')->andReturn($path = '/foo/bar.txt');
        $file->shouldReceive('getBasename')->andReturn('bar.txt');

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile')->andReturn($file);

        $this->scanner->expects('scan')->with($path)->andReturn(FileScanResult::MAX_SIZE_EXCEEDED);

        $this->sevenZipStrategy->expects('canProcess')->andReturnFalse();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(ClamAvUploadValidator::ERROR_TECHNICAL);

        $this->validator->onValidate($event);
    }

    public function testValidateContinuesWithoutExceptionWhenScannerReturnsSizeExceededButFileIsAZipArchive(): void
    {
        $file = \Mockery::mock(FileInterface::class);
        $file->shouldReceive('getPathname')->andReturn($path = '/foo/bar.zip');
        $file->shouldReceive('getBasename')->andReturn('bar.zip');

        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile')->andReturn($file);

        $this->scanner->expects('scan')->with($path)->andReturn(FileScanResult::MAX_SIZE_EXCEEDED);

        $this->sevenZipStrategy->expects('canProcess')->andReturnTrue();

        $this->validator->onValidate($event);
    }

    public function testValidateThrowsErrorWhenScannerReturnsATechnicalError(): void
    {
        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile->getPathname')->andReturn($path = '/foo/bar/non.existent');

        $this->scanner->expects('scan')->with($path)->andReturn(FileScanResult::TECHNICAL_ERROR);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(ClamAvUploadValidator::ERROR_TECHNICAL);

        $this->validator->onValidate($event);
    }

    public function testValidateThrowsErrorWhenScannerReturnsUnsafe(): void
    {
        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile->getPathname')->andReturn($path = '/foo/bar/non.existent');

        $this->scanner->expects('scan')->with($path)->andReturn(FileScanResult::UNSAFE);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(ClamAvUploadValidator::ERROR_UNSAFE);

        $this->validator->onValidate($event);
    }

    public function testValidateContinuesWithoutExceptionWhenScannerReturnsSafe(): void
    {
        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile->getPathname')->andReturn($path = '/foo/bar/non.existent');

        $this->scanner->expects('scan')->with($path)->andReturn(FileScanResult::SAFE);

        $this->validator->onValidate($event);
    }
}
