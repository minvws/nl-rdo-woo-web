<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Shared\Exception\FileReaderException;
use Shared\Exception\InquiryLinkImportException;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\Inquiry\InquiryLinkImportParser;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function iterator_to_array;
use function sprintf;

class InquiryLinkImportParserTest extends UnitTestCase
{
    public function testParseUsesPublicationContextForTheDocumentNumber(): void
    {
        $input = new UploadedFile(sprintf('%s/input-publicationcontext.xlsx', __DIR__), 'input-publicationcontext.xlsx');

        $this->assertMatchesSnapshot(
            iterator_to_array($this->getParser()->parse($input, 'TEST')),
        );
    }

    public function testParseFallsBackToPrefixAndMatterForTheDocumentNumber(): void
    {
        $input = new UploadedFile(sprintf('%s/input.xlsx', __DIR__), 'input.xlsx');

        $this->assertMatchesSnapshot(
            iterator_to_array($this->getParser()->parse($input, 'TEST')),
        );
    }

    public function testParseThrowsExceptionWhenBothColumnsArePresent(): void
    {
        $input = new UploadedFile(sprintf('%s/input-both-contexts.xlsx', __DIR__), 'input-both-contexts.xlsx');

        $this->expectException(InquiryLinkImportException::class);

        iterator_to_array($this->getParser()->parse($input, 'TEST'));
    }

    public function testParseThrowsExceptionWithoutMatterAndPublicationContextColumns(): void
    {
        $input = new UploadedFile(sprintf('%s/input-no-context.xlsx', __DIR__), 'input-no-context.xlsx');

        $this->expectException(FileReaderException::class);

        iterator_to_array($this->getParser()->parse($input, 'TEST'));
    }

    public function testPraseWithMatterColumn(): void
    {
        $input = new UploadedFile(sprintf('%s/input.xlsx', __DIR__), 'input.xlsx');

        self::assertTrue($this->getParser()->hasMatterColumn($input));
    }

    public function testPraseWithoutMatterColumn(): void
    {
        $input = new UploadedFile(sprintf('%s/input-publicationcontext.xlsx', __DIR__), 'input-publicationcontext.xlsx');

        self::assertFalse($this->getParser()->hasMatterColumn($input));
    }

    private function getParser(): InquiryLinkImportParser
    {
        return new InquiryLinkImportParser(
            new ExcelReaderFactory(),
        );
    }
}
