<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\DossierNotifications;
use App\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class DossierNotificationsTest extends UnitTestCase
{
    use MatchesSnapshots;

    public function testHasAnyDocumentNotificationsReturnsFalseForCompletedDossier(): void
    {
        $notifications = new DossierNotifications(false, 0, 0, 0);
        self::assertFalse($notifications->hasAnyDocumentNotifications());
    }

    public function testHasAnyDocumentNotificationsReturnsFalseForIncompleteDossierWithDocumentActions(): void
    {
        $notifications = new DossierNotifications(true, 0, 0, 0);
        self::assertFalse($notifications->hasAnyDocumentNotifications());
    }

    public function testHasAnyDocumentNotificationsReturnsTrueForMissingUploads(): void
    {
        $notifications = new DossierNotifications(false, 2, 0, 0);
        self::assertTrue($notifications->hasAnyDocumentNotifications());
    }

    public function testGetDossierNotificationsForIncompleteDossier(): void
    {
        $notifications = new DossierNotifications(true, 1, 2, 3);
        $this->assertMatchesSnapshot($notifications->getDossierNotifications());
    }

    public function testGetDossierNotificationsForCompleteDossier(): void
    {
        $notifications = new DossierNotifications(false, 0, 0, 0);
        $this->assertCount(0, $notifications->getDossierNotifications());
    }
}
