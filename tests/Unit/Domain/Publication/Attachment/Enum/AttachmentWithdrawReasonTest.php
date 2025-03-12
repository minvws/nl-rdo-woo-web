<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Enum;

use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AttachmentWithdrawReasonTest extends MockeryTestCase
{
    #[DataProvider('transDataProvider')]
    public function testTransKey(AttachmentWithdrawReason $reason, string $expectedKey, ?string $locale): void
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

        $reason->trans($translator, $locale);
    }

    /**
     * @return array<string,array{reason:AttachmentWithdrawReason,expectedKey:string,locale:?string}>
     */
    public static function transDataProvider(): array
    {
        return [
            'unrelated' => [
                'reason' => AttachmentWithdrawReason::UNRELATED,
                'expectedKey' => 'global.attachment.withdraw.reason.unrelated',
                'locale' => null,
            ],
            'incomplete' => [
                'reason' => AttachmentWithdrawReason::INCOMPLETE,
                'expectedKey' => 'global.attachment.withdraw.reason.incomplete',
                'locale' => 'nl',
            ],
        ];
    }
}
