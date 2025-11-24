<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DossierAdminActionTest extends UnitTestCase
{
    public function testTrans(): void
    {
        $locale = 'en_GB';
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with('admin.dossiers.action.label.' . DossierAdminAction::INGEST->value, [], null, $locale)
            ->andReturn('foo');

        self::assertEquals(
            'foo',
            DossierAdminAction::INGEST->trans($translator, $locale),
        );
    }
}
