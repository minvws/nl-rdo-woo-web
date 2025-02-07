<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DecisionTypeTest extends MockeryTestCase
{
    #[DataProvider('transDataProvider')]
    public function testTransKey(DecisionType $decisionType, string $expectedKey, ?string $locale): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator
            ->shouldReceive('trans')
            ->with(
                \Mockery::on(function (string $key) use ($expectedKey): bool {
                    $this->assertSame($expectedKey, $key, 'The translation key does not match expected value');

                    return true;
                }),
                [],
                null,
                $locale,
            );

        $decisionType->trans($translator, $locale);
    }

    /**
     * @return array<string,array{decisionType:DecisionType,expectedKey:string,locale:?string}>
     */
    public static function transDataProvider(): array
    {
        return [
            'single word' => [
                'decisionType' => DecisionType::PUBLIC,
                'expectedKey' => 'dossier.type.woo-decision.decision-type.public',
                'locale' => null,
            ],
            'multiple words' => [
                'decisionType' => DecisionType::ALREADY_PUBLIC,
                'expectedKey' => 'dossier.type.woo-decision.decision-type.already_public',
                'locale' => 'nl',
            ],
        ];
    }
}
