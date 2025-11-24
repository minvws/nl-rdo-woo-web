<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security\ApplicationMode;

use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Service\Security\ApplicationMode\ApplicationModeException;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ApplicationModeTest extends UnitTestCase
{
    use MatchesSnapshots;

    public function testIsAll(): void
    {
        self::assertTrue(ApplicationMode::ALL->isAll());
        self::assertFalse(ApplicationMode::ADMIN->isAll());
        self::assertFalse(ApplicationMode::API->isAll());
        self::assertFalse(ApplicationMode::PUBLIC->isAll());
        self::assertFalse(ApplicationMode::WORKER->isAll());
    }

    public function testIsAdmin(): void
    {
        self::assertTrue(ApplicationMode::ADMIN->isAdmin());
        self::assertFalse(ApplicationMode::API->isAdmin());
        self::assertFalse(ApplicationMode::PUBLIC->isAdmin());
        self::assertFalse(ApplicationMode::ALL->isAdmin());
        self::assertFalse(ApplicationMode::WORKER->isAdmin());
    }

    public function testIsAdminOrAll(): void
    {
        self::assertTrue(ApplicationMode::ADMIN->isAdminOrAll());
        self::assertFalse(ApplicationMode::API->isAdminOrAll());
        self::assertFalse(ApplicationMode::PUBLIC->isAdminOrAll());
        self::assertTrue(ApplicationMode::ALL->isAdminOrAll());
        self::assertFalse(ApplicationMode::WORKER->isAdminOrAll());
    }

    public function testIsPublic(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isPublic());
        self::assertFalse(ApplicationMode::API->isPublic());
        self::assertTrue(ApplicationMode::PUBLIC->isPublic());
        self::assertFalse(ApplicationMode::ALL->isPublic());
        self::assertFalse(ApplicationMode::WORKER->isPublic());
    }

    public function testIsPublicOrAll(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isPublicOrAll());
        self::assertFalse(ApplicationMode::API->isPublicOrAll());
        self::assertTrue(ApplicationMode::PUBLIC->isPublicOrAll());
        self::assertTrue(ApplicationMode::ALL->isPublicOrAll());
        self::assertFalse(ApplicationMode::WORKER->isPublicOrAll());
    }

    public function testIsApi(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isApi());
        self::assertTrue(ApplicationMode::API->isApi());
        self::assertFalse(ApplicationMode::PUBLIC->isApi());
        self::assertFalse(ApplicationMode::ALL->isApi());
        self::assertFalse(ApplicationMode::WORKER->isApi());
    }

    public function testIsApiOrAll(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isApiOrAll());
        self::assertTrue(ApplicationMode::API->isApiOrAll());
        self::assertFalse(ApplicationMode::PUBLIC->isApiOrAll());
        self::assertTrue(ApplicationMode::ALL->isApiOrAll());
        self::assertFalse(ApplicationMode::WORKER->isApiOrAll());
    }

    public function testIsWorker(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isWorker());
        self::assertFalse(ApplicationMode::API->isWorker());
        self::assertFalse(ApplicationMode::PUBLIC->isWorker());
        self::assertFalse(ApplicationMode::ALL->isWorker());
        self::assertTrue(ApplicationMode::WORKER->isWorker());
    }

    public function testIsWorkerOrAll(): void
    {
        self::assertFalse(ApplicationMode::ADMIN->isWorkerOrAll());
        self::assertFalse(ApplicationMode::API->isWorkerOrAll());
        self::assertFalse(ApplicationMode::PUBLIC->isWorkerOrAll());
        self::assertTrue(ApplicationMode::ALL->isWorkerOrAll());
        self::assertTrue(ApplicationMode::WORKER->isWorkerOrAll());
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
