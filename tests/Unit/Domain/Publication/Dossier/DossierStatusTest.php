<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DossierStatusTest extends UnitTestCase
{
    public function testIsDeleted(): void
    {
        self::assertTrue(DossierStatus::DELETED->isDeleted());
        self::assertFalse(DossierStatus::CONCEPT->isDeleted());
    }

    public function testIsNotDeleted(): void
    {
        self::assertTrue(DossierStatus::CONCEPT->isNotDeleted());
        self::assertFalse(DossierStatus::DELETED->isNotDeleted());
    }

    public function testIsPubliclyAvailableOrScheduled(): void
    {
        $snapshot = [];
        foreach (DossierStatus::cases() as $case) {
            $snapshot[$case->value] = $case->isPubliclyAvailableOrScheduled();
        }

        $this->assertMatchesYamlSnapshot([
            'test' => __FUNCTION__,
            'results_for' => $snapshot,
        ]);
    }

    public function testTrans(): void
    {
        $translation = 'foo';

        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator->expects('trans')->with('admin.publications.status.concept', [], null, null)->andReturn($translation);

        self::assertEquals(
            $translation,
            DossierStatus::CONCEPT->trans($translator),
        );
    }
}
