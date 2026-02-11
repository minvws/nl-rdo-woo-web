<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception;

use Mockery;
use Shared\Exception\TranslatableException;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatableExceptionTest extends UnitTestCase
{
    public function testTrans(): void
    {
        $exception = new class($message = 'foo', $translationKey = 'bar', $placeholders = ['x' => 'y']) extends TranslatableException {
        };

        $locale = 'nl_NL';

        $translator = Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with($translationKey, $placeholders, null, $locale)->andReturn($message)
            ->andReturn($translation = 'foo-bar');

        self::assertEquals(
            $translation,
            $exception->trans($translator, $locale),
        );
    }
}
