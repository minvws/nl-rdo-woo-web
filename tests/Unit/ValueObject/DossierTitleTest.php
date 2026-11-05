<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Exception\DossierTitleArgumentException;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\ExternalId;

use function str_repeat;

class DossierTitleTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $title = $this->getFaker()->regexify('[a-z]{3,10}');

        $dossierTitle = DossierTitle::create($title);
        $this->assertEquals($title, $dossierTitle->__toString());
    }

    public function testTitleIsEqual(): void
    {
        $titleOne = DossierTitle::create('foobar');
        $titleTwo = DossierTitle::create('foobar');

        $this->assertTrue($titleOne->equalTo($titleTwo));
        $this->assertTrue($titleTwo->equalTo($titleOne));
    }

    public function testTitleIsNotEqual(): void
    {
        $titleOne = DossierTitle::create('foobar-one');
        $titleTwo = DossierTitle::create('foobar-two');

        $this->assertFalse($titleOne->equalTo($titleTwo));
        $this->assertFalse($titleTwo->equalTo($titleOne));
    }

    #[DataProvider('otherTypeDataProvider')]
    public function testEqualToReturnsFalseForOtherTypes(mixed $other): void
    {
        $this->assertFalse(DossierTitle::create('foobar')->equalTo($other));
    }

    /**
     * @return array<string,array{other:mixed}>
     */
    public static function otherTypeDataProvider(): array
    {
        return [
            'null' => ['other' => null],
            'string with same value' => ['other' => 'foobar'],
            'other equatable with same value' => ['other' => ExternalId::create('foobar')],
        ];
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
            'string too short (0 chars)' => ['', 'Dossier title too short'],
            'string too short with spaces' => [' aa', 'Dossier title too short'],
            'string too long (> 500 chars)' => [
                $longString, 'Dossier title too long',
            ],
        ];
    }
}
