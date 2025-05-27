<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inquiry;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Service\FileReader\ExcelReaderFactory;
use App\Service\Inquiry\InquiryLinkImportParser;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InquiryLinkImportParserTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private InquiryLinkImportParser $parser;

    public function setUp(): void
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
