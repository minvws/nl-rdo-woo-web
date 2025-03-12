<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Enum;

use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('attachment')]
final class AttachmentTypeTest extends UnitTestCase
{
    public function testAttachmentTypeIsTranslatable(): void
    {
        $this->assertTrue(is_a(AttachmentType::class, TranslatableInterface::class, true), 'AttachmentType should be translatable');
    }

    public function testCasesAllHaveUniqueValues(): void
    {
        $values = array_column(AttachmentType::cases(), 'value');

        $this->assertCount(count($values), array_unique($values), 'All cases should have unique values');
    }

    public function testToArray(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $result = [];
        foreach (AttachmentType::cases() as $case) {
            $result[] = $case->toArray($translator);
        }

        $this->assertMatchesJsonSnapshot($result);
    }

    #[DataProvider('transDataProvider')]
    public function testTransKey(AttachmentType $attachmentType, string $expectedKey, ?string $locale): void
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
                AttachmentType::TRANS_DOMAIN,
                $locale,
            );

        $attachmentType->trans($translator, $locale);
    }

    /**
     * @return array<string,array{attachmentType:AttachmentType,expectedKey:string,locale:?string}>
     */
    public static function transDataProvider(): array
    {
        return [
            'single word' => [
                'attachmentType' => AttachmentType::ADVICE,
                'expectedKey' => 'advice',
                'locale' => null,
            ],
            'multiple words' => [
                'attachmentType' => AttachmentType::REQUEST_FOR_ADVICE,
                'expectedKey' => 'request_for_advice',
                'locale' => 'nl',
            ],
        ];
    }

    public function testGetCasesWithout(): void
    {
        $filteredCases = AttachmentType::getCasesWithout(AttachmentType::ANNUAL_REPORT);

        self::assertNotContains(AttachmentType::ANNUAL_REPORT, $filteredCases);
        self::assertContains(AttachmentType::OTHER, $filteredCases);
    }
}
