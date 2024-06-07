<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Judgement;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class JudgementTest extends MockeryTestCase
{
    public function testIsAlreadyPublic(): void
    {
        self::assertFalse(Judgement::NOT_PUBLIC->isAlreadyPublic());
        self::assertTrue(Judgement::ALREADY_PUBLIC->isAlreadyPublic());
    }

    public function testIsNotPublic(): void
    {
        self::assertFalse(Judgement::ALREADY_PUBLIC->isNotPublic());
        self::assertTrue(Judgement::NOT_PUBLIC->isNotPublic());
    }

    public function testIsAtLeastPartialPublic(): void
    {
        self::assertTrue(Judgement::PARTIAL_PUBLIC->isAtLeastPartialPublic());
        self::assertTrue(Judgement::PUBLIC->isAtLeastPartialPublic());
        self::assertFalse(Judgement::NOT_PUBLIC->isAtLeastPartialPublic());
        self::assertFalse(Judgement::ALREADY_PUBLIC->isAtLeastPartialPublic());
    }

    #[DataProvider('transDataProvider')]
    public function testTransKey(Judgement $judgement, string $expectedKey, ?string $locale): void
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

        $judgement->trans($translator, $locale);
    }

    /**
     * @return array<string,array{judgement:Judgement,expectedKey:string,locale:?string}>
     */
    public static function transDataProvider(): array
    {
        return [
            'single word' => [
                'judgement' => Judgement::PUBLIC,
                'expectedKey' => 'dossier.type.woo-decision.judgement.public',
                'locale' => null,
            ],
            'multiple words' => [
                'judgement' => Judgement::ALREADY_PUBLIC,
                'expectedKey' => 'dossier.type.woo-decision.judgement.already_public',
                'locale' => 'nl',
            ],
        ];
    }
}
