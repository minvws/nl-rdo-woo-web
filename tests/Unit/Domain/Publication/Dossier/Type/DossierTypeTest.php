<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DossierTypeTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $this->assertMatchesObjectSnapshot(DossierType::cases());
    }

    public function testIsCovenant(): void
    {
        $this->assertTrue(DossierType::COVENANT->isCovenant());
        $this->assertFalse(DossierType::WOO_DECISION->isCovenant());
    }

    public function testIsWooDecision(): void
    {
        $this->assertTrue(DossierType::WOO_DECISION->isWooDecision());
        $this->assertFalse(DossierType::COVENANT->isWooDecision());
    }

    public function testIsAnnualReport(): void
    {
        $this->assertTrue(DossierType::ANNUAL_REPORT->isAnnualReport());
        $this->assertFalse(DossierType::COVENANT->isAnnualReport());
    }

    public function testIsInvestigationReport(): void
    {
        $this->assertTrue(DossierType::INVESTIGATION_REPORT->isInvestigationReport());
        $this->assertFalse(DossierType::COVENANT->isInvestigationReport());
    }

    public function testIsOtherPublication(): void
    {
        $this->assertTrue(DossierType::OTHER_PUBLICATION->isOtherPublication());
        $this->assertFalse(DossierType::COVENANT->isInvestigationReport());
    }

    public function testIsAdvice(): void
    {
        $this->assertTrue(DossierType::ADVICE->isAdvice());
        $this->assertFalse(DossierType::ADVICE->isInvestigationReport());
    }

    public function testIsDisposition(): void
    {
        $this->assertTrue(DossierType::DISPOSITION->isDisposition());
        $this->assertFalse(DossierType::COVENANT->isDisposition());
    }

    public function testIsComplaintJudgment(): void
    {
        $this->assertTrue(DossierType::COMPLAINT_JUDGEMENT->isComplaintJudgement());
        $this->assertFalse(DossierType::COVENANT->isComplaintJudgement());
    }

    public function testIsRequestForAdvice(): void
    {
        $this->assertTrue(DossierType::REQUEST_FOR_ADVICE->isRequestForAdvice());
        $this->assertFalse(DossierType::COVENANT->isRequestForAdvice());
    }

    #[DataProvider('transDataProvider')]
    public function testTransKey(DossierType $dossierType, string $expectedKey, ?string $locale): void
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

        $dossierType->trans($translator, $locale);
    }

    /**
     * @return array<string,array{dossierType:DossierType,expectedKey:string,locale:?string}>
     */
    public static function transDataProvider(): array
    {
        return [
            'case WOO_DECISION' => [
                'dossierType' => DossierType::WOO_DECISION,
                'expectedKey' => 'dossier.type.woo-decision',
                'locale' => null,
            ],
            'case COVENANT' => [
                'dossierType' => DossierType::COVENANT,
                'expectedKey' => 'dossier.type.covenant',
                'locale' => 'nl',
            ],
        ];
    }
}
