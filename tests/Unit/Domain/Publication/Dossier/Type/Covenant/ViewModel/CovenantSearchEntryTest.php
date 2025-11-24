<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Publication\SourceType;

class CovenantSearchEntryTest extends TestCase
{
    #[DataProvider('createProvider')]
    public function testCreate(?string $input, SourceType $expectedResult): void
    {
        self::assertEquals($expectedResult, SourceType::create($input));
    }

    /**
     * @return array<string, array{input:?string, expectedResult:SourceType}>
     */
    public static function createProvider(): array
    {
        return [
            'empty-string' => [
                'input' => '',
                'expectedResult' => SourceType::UNKNOWN,
            ],
            'null' => [
                'input' => null,
                'expectedResult' => SourceType::UNKNOWN,
            ],
            'PDF-whitespaced-and-uppercased' => [
                'input' => ' PDF   ',
                'expectedResult' => SourceType::PDF,
            ],
            'mimetype' => [
                'input' => 'application/vnd.openxmlformats-officedocument',
                'expectedResult' => SourceType::DOC,
            ],
        ];
    }
}
