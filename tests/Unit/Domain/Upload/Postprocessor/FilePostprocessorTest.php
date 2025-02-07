<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Postprocessor;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Upload\Postprocessor\FilePostprocessor;
use App\Domain\Upload\Postprocessor\FilePostprocessorStrategyInterface;
use App\Domain\Upload\Postprocessor\NoMatchingFilePostprocessorException;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\UploadedFile;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class FilePostprocessorTest extends UnitTestCase
{
    private FilePostprocessorStrategyInterface&MockInterface $firstPostprocessor;
    private FilePostprocessorStrategyInterface&MockInterface $secondPostprocessor;
    private UploadedFile&MockInterface $file;
    private WooDecision&MockInterface $dossier;
    private string $documentId;
    private DocumentNumberExtractor&MockInterface $documentNumberExtractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firstPostprocessor = \Mockery::mock(FilePostprocessorStrategyInterface::class);
        $this->secondPostprocessor = \Mockery::mock(FilePostprocessorStrategyInterface::class);

        $this->file = \Mockery::mock(UploadedFile::class);
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->documentId = 'documentId';

        $this->documentNumberExtractor = \Mockery::mock(DocumentNumberExtractor::class);
        $this->documentNumberExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($originalFile, $this->dossier)
            ->andReturn($this->documentId);
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
            ->with($this->file, $this->dossier, $this->documentId)
            ->andReturnTrue();

        $preprocessor = new FilePostprocessor(
            $this->documentNumberExtractor,
            [$this->firstPostprocessor, $this->secondPostprocessor],
        );
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
            ->with($this->file, $this->dossier, $this->documentId)
            ->andReturnTrue();
        $this->secondPostprocessor->shouldNotReceive('process');

        $preprocessor = new FilePostprocessor(
            $this->documentNumberExtractor,
            new \ArrayIterator([$this->firstPostprocessor, $this->secondPostprocessor]),
        );
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

        $preprocessor = new FilePostprocessor(
            $this->documentNumberExtractor,
            [$this->firstPostprocessor, $this->secondPostprocessor],
        );

        $this->expectExceptionObject(NoMatchingFilePostprocessorException::create($this->file, $this->dossier));

        $preprocessor->process($this->file, $this->dossier);
    }
}
