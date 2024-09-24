<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Postprocessor;

use App\Domain\Upload\Postprocessor\FilePostprocessor;
use App\Domain\Upload\Postprocessor\FilePostprocessorStrategyInterface;
use App\Domain\Upload\Postprocessor\NoMatchingFilePostprocessorException;
use App\Domain\Upload\UploadedFile;
use App\Entity\Dossier;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class FilePostprocessorTest extends UnitTestCase
{
    private FilePostprocessorStrategyInterface&MockInterface $firstPostprocessor;
    private FilePostprocessorStrategyInterface&MockInterface $secondPostprocessor;
    private UploadedFile&MockInterface $file;
    private Dossier&MockInterface $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firstPostprocessor = \Mockery::mock(FilePostprocessorStrategyInterface::class);
        $this->secondPostprocessor = \Mockery::mock(FilePostprocessorStrategyInterface::class);

        $this->file = \Mockery::mock(UploadedFile::class);
        $this->dossier = \Mockery::mock(Dossier::class);
    }

    public function testProcessPassingArrayOfStrategies(): void
    {
        $this->firstPostprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file, $this->dossier)
            ->andReturnFalse();
        $this->secondPostprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file, $this->dossier)
            ->andReturnTrue();

        $this->firstPostprocessor->shouldNotReceive('process');
        $this->secondPostprocessor
            ->shouldReceive('process')
            ->with($this->file, $this->dossier)
            ->andReturnTrue();

        $preprocessor = new FilePostprocessor([$this->firstPostprocessor, $this->secondPostprocessor]);
        $preprocessor->process($this->file, $this->dossier);
    }

    public function testProcessPassingTraversableOfStrategies(): void
    {
        $this->firstPostprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file, $this->dossier)
            ->andReturnTrue();
        $this->secondPostprocessor->shouldNotReceive('canProcess');

        $this->firstPostprocessor
            ->shouldReceive('process')
            ->with($this->file, $this->dossier)
            ->andReturnTrue();
        $this->secondPostprocessor->shouldNotReceive('process');

        $preprocessor = new FilePostprocessor(new \ArrayIterator([$this->firstPostprocessor, $this->secondPostprocessor]));
        $preprocessor->process($this->file, $this->dossier);
    }

    public function testProcessThrowsExceptionWhenNoMatchingFileProcessorIsFound(): void
    {
        $this->firstPostprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file, $this->dossier)
            ->andReturnFalse();
        $this->secondPostprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file, $this->dossier)
            ->andReturnFalse();

        $this->firstPostprocessor->shouldNotReceive('process');
        $this->secondPostprocessor->shouldNotReceive('process');

        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn('file.xsl');

        $this->dossier
            ->shouldReceive('getId')
            ->andReturn(Uuid::v6());

        $preprocessor = new FilePostprocessor([$this->firstPostprocessor, $this->secondPostprocessor]);

        $this->expectExceptionObject(NoMatchingFilePostprocessorException::create($this->file, $this->dossier));

        $preprocessor->process($this->file, $this->dossier);
    }
}
