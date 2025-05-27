<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Enum;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Tests\Unit\UnitTestCase as UnitUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AttachmentLanguageTest extends UnitUnitTestCase
{
    public function testAttachmentLanguageIsTranslatable(): void
    {
        $this->assertInstanceOf(TranslatableInterface::class, AttachmentLanguage::DUTCH, 'AttachmentLanguage should be translatable');
    }

    public function testCasesAllHAveUniqueValues(): void
    {
        $values = array_column(AttachmentLanguage::cases(), 'value');

        $this->assertCount(count($values), array_unique($values), 'All cases should have unique values');
    }

    public function testToArray(): void
    {
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnArg(0);

        $result = [];
        foreach (AttachmentLanguage::cases() as $case) {
            $result[] = $case->toArray($translator);
        }

        $this->assertMatchesJsonSnapshot($result);
    }

    #[DataProvider('transDataProvider')]
    public function testTransKey(AttachmentLanguage $attachmentLanguage, string $expectedKey, ?string $locale): void
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
                AttachmentLanguage::TRANS_DOMAIN,
                $locale,
            );

        $attachmentLanguage->trans($translator, $locale);
    }

    /**
     * @return array<string,array{attachmentLanguage:AttachmentLanguage,expectedKey:string,locale:?string}>
     */
    public static function transDataProvider(): array
    {
        return [
            'case DUTCH' => [
                'attachmentLanguage' => AttachmentLanguage::DUTCH,
                'expectedKey' => 'dutch',
                'locale' => null,
            ],
            'case ENGLISH' => [
                'attachmentLanguage' => AttachmentLanguage::ENGLISH,
                'expectedKey' => 'english',
                'locale' => 'nl',
            ],
        ];
    }
}
