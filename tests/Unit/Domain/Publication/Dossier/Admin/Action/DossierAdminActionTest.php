<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DossierAdminActionTest extends MockeryTestCase
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
