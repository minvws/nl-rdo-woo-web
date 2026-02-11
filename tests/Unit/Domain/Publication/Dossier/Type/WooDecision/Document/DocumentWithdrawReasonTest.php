<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DocumentWithdrawReasonTest extends UnitTestCase
{
    public function testTrans(): void
    {
        $locale = 'nl';

        $translator = Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with(
                'global.document.withdraw.reason.data_in_document',
                [],
                null,
                $locale,
            )->andReturn('foo');

        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;

        self::assertEquals(
            'foo',
            $reason->trans($translator, $locale),
        );
    }
}
