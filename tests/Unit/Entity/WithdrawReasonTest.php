<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\WithdrawReason;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WithdrawReasonTest extends MockeryTestCase
{
    public function testTrans(): void
    {
        $locale = 'nl';

        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with(
                'global.document.withdraw.reason.data_in_document',
                [],
                null,
                $locale,
            )->andReturn('foo');

        $reason = WithdrawReason::DATA_IN_DOCUMENT;

        self::assertEquals(
            'foo',
            $reason->trans($translator, $locale),
        );
    }
}
