<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\Inquiry\InquiryLinkImportParser;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function iterator_to_array;

class InquiryLinkImportParserTest extends UnitTestCase
{
    public function testParse(): void
    {
        $input = new UploadedFile(__DIR__ . '/input.xlsx', 'input.xlsx');

        $prefix = new DocumentPrefix('TEST');

        $parser = new InquiryLinkImportParser(
            new ExcelReaderFactory(),
        );

        $this->assertMatchesSnapshot(
            iterator_to_array($parser->parse($input, $prefix)),
        );
    }
}
