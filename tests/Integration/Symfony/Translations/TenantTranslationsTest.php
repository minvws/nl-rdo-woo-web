<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Symfony\Translations;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\TenantId;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TenantTranslationsTest extends SharedWebTestCase
{
    #[DataProvider('tenantTranslationIntroLabelOgaDataProvider')]
    public function testFoo(TenantId $tenantId, string $expectedResult): void
    {
        self::bootKernel(['tenantId' => $tenantId]);

        $translator = $this->getContainer()->get(TranslatorInterface::class);

        $this->assertEquals($expectedResult, $translator->trans('public.intro.label.oga'));
    }

    /**
     * @return array<string, array{TenantId, string}>
     */
    public static function tenantTranslationIntroLabelOgaDataProvider(): array
    {
        return [
            'minfin' => [TenantId::MINFIN, 'OpenFIN'],
            'minvws' => [TenantId::MINVWS, 'OpenVWS'],
        ];
    }
}
