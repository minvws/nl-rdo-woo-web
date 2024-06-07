<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PublicationReasonTest extends MockeryTestCase
{
    public function testGetDefault(): void
    {
        $this->assertEquals(PublicationReason::WOO_REQUEST, PublicationReason::getDefault());
    }

    #[DataProvider('transDataProvider')]
    public function testTransKey(PublicationReason $publicationReason, string $expectedKey, ?string $locale): void
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

        $publicationReason->trans($translator, $locale);
    }

    /**
     * @return array<string,array{publicationReason:PublicationReason,expectedKey:string,locale:?string}>
     */
    public static function transDataProvider(): array
    {
        return [
            'woo' => [
                'publicationReason' => PublicationReason::WOO_REQUEST,
                'expectedKey' => 'dossier.type.woo-decision.publication-reason.woo_request',
                'locale' => null,
            ],
            'wob' => [
                'publicationReason' => PublicationReason::WOB_REQUEST,
                'expectedKey' => 'dossier.type.woo-decision.publication-reason.wob_request',
                'locale' => 'nl',
            ],
        ];
    }
}
