<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Preprocessor;

use App\Domain\Upload\Preprocessor\FilePreprocessor;
use App\Domain\Upload\Preprocessor\FilePreprocessorStrategyInterface;
use App\Domain\Upload\UploadedFile;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class FilePreprocessorTest extends UnitTestCase
{
    use IterableToGenerator;

    private UploadedFile&MockInterface $file;
    private FilePreprocessorStrategyInterface&MockInterface $firstPreprocessor;
    private FilePreprocessorStrategyInterface&MockInterface $secondPreprocessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = \Mockery::mock(UploadedFile::class);
        $this->firstPreprocessor = \Mockery::mock(FilePreprocessorStrategyInterface::class);
        $this->secondPreprocessor = \Mockery::mock(FilePreprocessorStrategyInterface::class);
    }

    public function testProcessPassingArrayOfStrategies(): void
    {
        $this->firstPreprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file)
            ->andReturnFalse();
        $this->secondPreprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file)
            ->andReturnTrue();

        $this->firstPreprocessor->shouldNotReceive('process');
        $this->secondPreprocessor
            ->shouldReceive('process')
            ->with($this->file)
            ->andReturn($this->iterableToGenerator($expectedResult = [\Mockery::mock(UploadedFile::class), \Mockery::mock(UploadedFile::class)]));

        $preprocessor = new FilePreprocessor([$this->firstPreprocessor, $this->secondPreprocessor]);
        $result = $preprocessor->process($this->file);

        $this->assertEquals($expectedResult, iterator_to_array($result));
    }

    public function testProcessPassingTraversableOfStrategies(): void
    {
        $this->firstPreprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file)
            ->andReturnTrue();
        $this->secondPreprocessor->shouldNotReceive('canProcess');

        $this->firstPreprocessor
            ->shouldReceive('process')
            ->with($this->file)
            ->andReturn($this->iterableToGenerator($expectedResult = [\Mockery::mock(UploadedFile::class), \Mockery::mock(UploadedFile::class)]));
        $this->secondPreprocessor->shouldNotReceive('process');

        $preprocessor = new FilePreprocessor(new \ArrayIterator([$this->firstPreprocessor, $this->secondPreprocessor]));
        $result = $preprocessor->process($this->file);

        $this->assertEquals($expectedResult, iterator_to_array($result));
    }

    public function testProcessReturningOriginalFile(): void
    {
        $this->firstPreprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file)
            ->andReturnFalse();
        $this->secondPreprocessor
            ->shouldReceive('canProcess')
            ->once()
            ->with($this->file)
            ->andReturnFalse();

        $this->firstPreprocessor->shouldNotReceive('process');
        $this->secondPreprocessor->shouldNotReceive('process');

        $preprocessor = new FilePreprocessor([$this->firstPreprocessor, $this->secondPreprocessor]);
        $result = $preprocessor->process($this->file);

        $this->assertEquals([$this->file], iterator_to_array($result));
    }
}
