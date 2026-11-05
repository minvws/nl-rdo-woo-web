<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Mockery;
use Mockery\MockInterface;
use Shared\Service\Import\ImportError;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryLinkImportResult;
use Shared\Tests\Unit\UnitTestCase;

class InquiryLinkImportResultTest extends UnitTestCase
{
    private MockInterface&InquiryChangeset $changeset;
    private InquiryLinkImportResult $result;

    protected function setUp(): void
    {
        $this->changeset = Mockery::mock(InquiryChangeset::class);
        $this->result = new InquiryLinkImportResult($this->changeset);

        parent::setUp();
    }

    public function testAddGenericError(): void
    {
        $this->assertFalse($this->result->hasGenericErrors());

        $importError = new ImportError($this->getFaker()->sentence(), $this->getFaker()->word());
        $this->result->addGenericError($importError);

        $this->assertEquals([$importError], $this->result->genericErrors);
        $this->assertTrue($this->result->hasGenericErrors());
    }

    public function testAddRowError(): void
    {
        $this->assertFalse($this->result->hasRowErrors());

        $rowNumber = $this->getFaker()->randomNumber();
        $importError = new ImportError($this->getFaker()->sentence(), $this->getFaker()->word());
        $this->result->addRowError($rowNumber, $importError);

        $this->assertEquals([$rowNumber => [$importError]], $this->result->rowErrors);
        $this->assertTrue($this->result->hasRowErrors());
    }

    public function testGetAddedRelationsCount(): void
    {
        $this->changeset->expects('getChanges')->andReturn([
            'foo-123' => [
                InquiryChangeset::ADD_DOCUMENTS => ['x', 'y', 'z'],
                InquiryChangeset::ADD_DOSSIERS => ['x', 'y', 'z'],
                InquiryChangeset::DEL_DOCUMENTS => ['q'],
            ],
            'bar-456' => [
                InquiryChangeset::ADD_DOCUMENTS => ['x', 'y'],
            ],
        ]);

        self::assertEquals(5, $this->result->getAddedRelationsCount());
    }

    public function testIsSuccessfulReturnsTrueForNoErrorsAndOneAddedDocument(): void
    {
        $this->changeset->expects('getChanges')->andReturn([
            'foo-123' => [
                InquiryChangeset::ADD_DOCUMENTS => ['x'],
            ],
        ]);

        self::assertTrue($this->result->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseForNoErrorsAndNoAddedDocument(): void
    {
        $this->changeset->expects('getChanges')->andReturn([]);

        self::assertFalse($this->result->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseWhenDocumentIsAddedWithARowError(): void
    {
        $importError = new ImportError($this->getFaker()->sentence(), $this->getFaker()->word());
        $this->result->addRowError($this->getFaker()->randomNumber(), $importError);

        self::assertFalse($this->result->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseWhenDocumentIsAddedWithAGenericError(): void
    {
        $importError = new ImportError($this->getFaker()->sentence(), $this->getFaker()->word());
        $this->result->addGenericError($importError);

        self::assertFalse($this->result->isSuccessful());
    }

    public function testSetHasMatter(): void
    {
        $hasMatter = $this->getFaker()->boolean();

        $this->result->setHasMatter($hasMatter);

        self::assertSame($hasMatter, $this->result->hasMatter());
    }
}
