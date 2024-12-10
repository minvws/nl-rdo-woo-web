<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Upload\Postprocessor\FilePostprocessor;
use App\Domain\Upload\Postprocessor\NoMatchingFilePostprocessorException;
use App\Domain\Upload\Preprocessor\FilePreprocessor;
use App\Domain\Upload\Process\FileProcessor;
use App\Domain\Upload\UploadedFile;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class FileProcessorTest extends UnitTestCase
{
    use IterableToGenerator;

    private LoggerInterface&MockInterface $logger;
    private FilePreprocessor&MockInterface $filePreprocessor;
    private FilePostprocessor&MockInterface $filePostprocessor;
    private UploadedFile&MockInterface $file;
    private WooDecision&MockInterface $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->filePreprocessor = \Mockery::mock(FilePreprocessor::class);
        $this->filePostprocessor = \Mockery::mock(FilePostprocessor::class);
        $this->file = \Mockery::mock(UploadedFile::class);
        $this->dossier = \Mockery::mock(WooDecision::class);
    }

    public function testProcess(): void
    {
        /** @var UploadedFile&MockInterface $fileOne */
        $fileOne = \Mockery::mock(UploadedFile::class);
        /** @var UploadedFile&MockInterface $fileTwo */
        $fileTwo = \Mockery::mock(UploadedFile::class);

        $files = $this->iterableToGenerator([$fileOne, $fileTwo]);

        $fileOne
            ->shouldReceive('getOriginalFilename')
            ->andReturn($filename = 'file_one.pdf');
        $this->dossier
            ->shouldReceive('getId')
            ->andReturn($dossierId = Uuid::v6());

        $this->filePreprocessor
            ->shouldReceive('process')
            ->once()
            ->with($this->file)
            ->andReturn($files);

        $this->filePostprocessor
            ->shouldReceive('process')
            ->once()
            ->with($fileOne, $this->dossier)
            ->andThrow(NoMatchingFilePostprocessorException::create($fileOne, $this->dossier));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('No matching file Postprocessor found', [
                'filename' => $filename,
                'dossierId' => $dossierId,
            ]);

        $this->filePostprocessor
            ->shouldReceive('process')
            ->once()
            ->with($fileTwo, $this->dossier);

        $processor = new FileProcessor($this->logger, $this->filePreprocessor, $this->filePostprocessor);
        $processor->process($this->file, $this->dossier);
    }
}
