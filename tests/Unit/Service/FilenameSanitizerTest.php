<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Shared\Service\FilenameSanitizer;
use Shared\Tests\Unit\UnitTestCase;

use function chr;
use function htmlentities;
use function sprintf;

class FilenameSanitizerTest extends UnitTestCase
{
    public function testStripAdditionalCharacters(): void
    {
        $word = $this->getFaker()->word();

        $filenameSanitizer = new FilenameSanitizer(sprintf('%s%s', $word, chr(127)));
        $result = $filenameSanitizer->stripAdditionalCharacters();

        self::assertEquals($word, $result->getFilename());
    }

    public function testStripPhp(): void
    {
        $filename = sprintf('%s%s', $this->getFaker()->word(), '<');
        $filenameSanitizer = new FilenameSanitizer($filename);
        $result = $filenameSanitizer->stripPhp();

        self::assertEquals(htmlentities($filename), $result->getFilename());
    }

    public function testStripRiskyCharacters(): void
    {
        $word = $this->getFaker()->word();

        $filenameSanitizer = new FilenameSanitizer(sprintf('%s %s', $word, $word));
        $result = $filenameSanitizer->stripRiskyCharacters();

        self::assertEquals(sprintf('%s_%s', $word, $word), $result->getFilename());
    }
}
