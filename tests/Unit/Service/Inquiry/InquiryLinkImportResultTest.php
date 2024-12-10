<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inquiry;

use App\Exception\InquiryLinkImportException;
use App\Service\Inquiry\InquiryChangeset;
use App\Service\Inquiry\InquiryLinkImportResult;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InquiryLinkImportResultTest extends MockeryTestCase
{
    private MockInterface&InquiryChangeset $changeset;
    private InquiryLinkImportResult $result;

    public function setUp(): void
    {
        $this->changeset = \Mockery::mock(InquiryChangeset::class);
        $this->result = new InquiryLinkImportResult($this->changeset);

        parent::setUp();
    }

    public function testAddGenericException(): void
    {
        $this->assertFalse($this->result->hasGenericExceptions());

        $exception = InquiryLinkImportException::forMissingDocument('foo-123');
        $this->result->addGenericException($exception);

        $this->assertEquals([$exception], $this->result->genericExceptions);
        $this->assertTrue($this->result->hasGenericExceptions());
    }

    public function testAddRowException(): void
    {
        $this->assertFalse($this->result->hasRowExceptions());

        $exception = InquiryLinkImportException::forMissingDocument('foo-123');
        $this->result->addRowException(123, $exception);

        $this->assertEquals([123 => [$exception]], $this->result->rowExceptions);
        $this->assertTrue($this->result->hasRowExceptions());
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
        $this->changeset->shouldReceive('getChanges')->andReturn([
            'foo-123' => [
                InquiryChangeset::ADD_DOCUMENTS => ['x'],
            ],
        ]);

        $exception = InquiryLinkImportException::forMissingDocument('foo-123');
        $this->result->addRowException(123, $exception);

        self::assertFalse($this->result->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseWhenDocumentIsAddedWithAGenericError(): void
    {
        $this->changeset->shouldReceive('getChanges')->andReturn([
            'foo-123' => [
                InquiryChangeset::ADD_DOCUMENTS => ['x'],
            ],
        ]);

        $exception = InquiryLinkImportException::forMissingDocument('foo-123');
        $this->result->addGenericException($exception);

        self::assertFalse($this->result->isSuccessful());
    }
}
