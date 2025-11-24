<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\Inquiry\InquiryLinkImportParser;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InquiryLinkImportParserTest extends UnitTestCase
{
    use MatchesSnapshots;

    private InquiryLinkImportParser $parser;

    protected function setUp(): void
    {
        $this->parser = new InquiryLinkImportParser(
            new ExcelReaderFactory(),
        );

        parent::setUp();
    }

    public function testParse(): void
    {
        $input = new UploadedFile(__DIR__ . '/input.xlsx', 'input.xlsx');

        $prefix = new DocumentPrefix();
        $prefix->setPrefix('TEST');

        $this->assertMatchesSnapshot(
            iterator_to_array($this->parser->parse($input, $prefix)),
        );
    }
}
