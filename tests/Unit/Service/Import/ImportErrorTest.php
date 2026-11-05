<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Import;

use Mockery;
use Shared\Exception\Import\RemarkTooLongException;
use Shared\Service\Import\ImportError;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;

final class ImportErrorTest extends UnitTestCase
{
    public function testTrans(): void
    {
        $translationKey = $this->getFaker()->word();
        $placeholders = ['{' . $this->getFaker()->word() . '}' => $this->getFaker()->word()];
        $locale = $this->getFaker()->locale();
        $translation = $this->getFaker()->sentence();

        $importError = new ImportError($this->getFaker()->sentence(), $translationKey, $placeholders);

        $translator = Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with($translationKey, $placeholders, null, $locale)
            ->andReturn($translation);

        self::assertSame($translation, $importError->trans($translator, $locale));
    }

    public function testFromException(): void
    {
        $rowIndex = $this->getFaker()->randomNumber();
        $max = $this->getFaker()->randomNumber();
        $importException = new RemarkTooLongException($rowIndex, $max);

        $importError = ImportError::fromException($importException);

        self::assertSame($importException->getMessage(), $importError->message);
        self::assertSame('publication.dossier.error.remark_too_long', $importError->translationKey);
        self::assertSame([
            '{rownumber}' => strval($rowIndex),
            '{max}' => strval($max),
        ], $importError->placeholders);
    }
}
