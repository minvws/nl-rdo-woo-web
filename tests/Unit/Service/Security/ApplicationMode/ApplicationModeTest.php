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

    public function testIsAll(): void
    {
        self::assertTrue(ApplicationMode::ALL->isAll());
        self::assertFalse(ApplicationMode::ADMIN->isAll());
        self::assertFalse(ApplicationMode::API->isAll());
        self::assertFalse(ApplicationMode::PUBLIC->isAll());
    }

    public function testIsAdmin(): void
    {
        self::assertTrue(ApplicationMode::ADMIN->isAdmin());
        self::assertFalse(ApplicationMode::API->isAdmin());
        self::assertFalse(ApplicationMode::PUBLIC->isAdmin());
        self::assertFalse(ApplicationMode::ALL->isAdmin());
    }

    public function testIsAdminOrAll(): void
    {
        self::assertTrue(ApplicationMode::ADMIN->isAdminOrAll());
        self::assertFalse(ApplicationMode::API->isAdminOrAll());
        self::assertFalse(ApplicationMode::PUBLIC->isAdminOrAll());
        self::assertTrue(ApplicationMode::ALL->isAdminOrAll());
    }

    public function testIsPublic(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isPublic());
        self::assertFalse(ApplicationMode::API->isPublic());
        self::assertTrue(ApplicationMode::PUBLIC->isPublic());
        self::assertFalse(ApplicationMode::ALL->isPublic());
    }

    public function testIsPublicOrAll(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isPublicOrAll());
        self::assertFalse(ApplicationMode::API->isPublicOrAll());
        self::assertTrue(ApplicationMode::PUBLIC->isPublicOrAll());
        self::assertTrue(ApplicationMode::ALL->isPublicOrAll());
    }

    public function testIsApi(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isApi());
        self::assertTrue(ApplicationMode::API->isApi());
        self::assertFalse(ApplicationMode::PUBLIC->isApi());
        self::assertFalse(ApplicationMode::ALL->isApi());
    }

    public function testIsApiOrAll(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isApiOrAll());
        self::assertTrue(ApplicationMode::API->isApiOrAll());
        self::assertFalse(ApplicationMode::PUBLIC->isApiOrAll());
        self::assertTrue(ApplicationMode::ALL->isApiOrAll());
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
