<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Exception\DossierTitleArgumentException;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;

use function str_repeat;

class DossierTitleTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $title = $this->getFaker()->regexify('[a-z]{3,10}');

        $dossierTitle = DossierTitle::create($title);
        $this->assertEquals($title, $dossierTitle->__toString());
    }

    #[DataProvider('invalidDossierTitleDataProvider')]
    public function testCreateWithInvalidString(string $title, string $message): void
    {
        $this->expectException(DossierTitleArgumentException::class);
        $this->expectExceptionMessageIs($message);
        DossierTitle::create($title);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function invalidDossierTitleDataProvider(): array
    {
        $longString = str_repeat('a', 501);

        return [
            'string too short (0 chars)' => ['', 'dossier.title_too_short'],
            'string too short with spaces' => [' aa', 'dossier.title_too_short'],
            'string too long (> 500 chars)' => [
                $longString, 'dossier.title_too_long',
            ],
        ];
    }
}
