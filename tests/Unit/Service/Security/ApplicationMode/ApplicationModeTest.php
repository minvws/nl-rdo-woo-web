<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security\ApplicationMode;

use App\Service\Security\ApplicationMode\ApplicationMode;
use App\Service\Security\ApplicationMode\ApplicationModeException;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ApplicationModeTest extends MockeryTestCase
{
    use MatchesSnapshots;

    public function testIsAdmin(): void
    {
        self::assertTrue(ApplicationMode::ADMIN->isAdmin());
        self::assertFalse(ApplicationMode::ALL->isAdmin());
        self::assertFalse(ApplicationMode::API->isAdmin());
        self::assertFalse(ApplicationMode::PUBLIC->isAdmin());
    }

    public function testIsPublic(): void
    {
        self::assertTrue(ApplicationMode::PUBLIC->isPublic());
        self::assertFalse(ApplicationMode::ALL->isPublic());
        self::assertFalse(ApplicationMode::API->isPublic());
        self::assertFalse(ApplicationMode::ADMIN->isPublic());
    }

    public function testIsApi(): void
    {
        self::assertTrue(ApplicationMode::API->isApi());
        self::assertFalse(ApplicationMode::ALL->isApi());
        self::assertFalse(ApplicationMode::ADMIN->isApi());
        self::assertFalse(ApplicationMode::PUBLIC->isApi());
    }

    public function testIsAll(): void
    {
        self::assertTrue(ApplicationMode::ALL->isAll());
        self::assertFalse(ApplicationMode::ADMIN->isAll());
        self::assertFalse(ApplicationMode::API->isAll());
        self::assertFalse(ApplicationMode::PUBLIC->isAll());
    }

    public function testGetAccessibleDossierStatusesForPublic(): void
    {
        $this->assertMatchesSnapshot(ApplicationMode::PUBLIC->getAccessibleDossierStatuses());
    }

    public function testGetAccessibleDossierStatusesForAdmin(): void
    {
        $this->assertMatchesSnapshot(ApplicationMode::ADMIN->getAccessibleDossierStatuses());
    }

    public function testGetAccessibleDossierStatusesForApi(): void
    {
        $this->expectException(ApplicationModeException::class);
        ApplicationMode::API->getAccessibleDossierStatuses();
    }

    public function testGetAccessibleDossierStatusesForAll(): void
    {
        $this->expectException(ApplicationModeException::class);
        ApplicationMode::ALL->getAccessibleDossierStatuses();
    }

    public function testFromEnvVarAcceptsLowerCaseValue(): void
    {
        self::assertEquals(ApplicationMode::ALL, ApplicationMode::fromEnvVar('all'));
    }
}
