<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\DossierStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DossierStatusTest extends MockeryTestCase
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
}
