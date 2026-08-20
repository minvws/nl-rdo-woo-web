<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\ApplicationId;
use Shared\Service\Security\ApplicationId\ApplicationIdException;
use Shared\Tests\Unit\UnitTestCase;
use ValueError;

final class ApplicationIdTest extends UnitTestCase
{
    public function testIsAdmin(): void
    {
        $this->assertTrue(ApplicationId::ADMIN->isAdmin());
        $this->assertFalse(ApplicationId::PUBLIC->isAdmin());
        $this->assertFalse(ApplicationId::PUBLICATION_API->isAdmin());
        $this->assertFalse(ApplicationId::WORKER->isAdmin());
        $this->assertFalse(ApplicationId::SHARED->isAdmin());
    }

    public function testIsPublic(): void
    {
        $this->assertFalse(ApplicationId::ADMIN->isPublic());
        $this->assertTrue(ApplicationId::PUBLIC->isPublic());
        $this->assertFalse(ApplicationId::PUBLICATION_API->isPublic());
        $this->assertFalse(ApplicationId::WORKER->isPublic());
        $this->assertFalse(ApplicationId::SHARED->isPublic());
    }

    public function testIsPublicationApi(): void
    {
        $this->assertFalse(ApplicationId::ADMIN->isPublicationApi());
        $this->assertFalse(ApplicationId::PUBLIC->isPublicationApi());
        $this->assertTrue(ApplicationId::PUBLICATION_API->isPublicationApi());
        $this->assertFalse(ApplicationId::WORKER->isPublicationApi());
        $this->assertFalse(ApplicationId::SHARED->isPublicationApi());
    }

    public function testIsWorker(): void
    {
        $this->assertFalse(ApplicationId::ADMIN->isWorker());
        $this->assertFalse(ApplicationId::PUBLIC->isWorker());
        $this->assertFalse(ApplicationId::PUBLICATION_API->isWorker());
        $this->assertTrue(ApplicationId::WORKER->isWorker());
        $this->assertFalse(ApplicationId::SHARED->isWorker());
    }

    public function testIsShared(): void
    {
        $this->assertFalse(ApplicationId::ADMIN->isShared());
        $this->assertFalse(ApplicationId::PUBLIC->isShared());
        $this->assertFalse(ApplicationId::PUBLICATION_API->isShared());
        $this->assertFalse(ApplicationId::WORKER->isShared());
        $this->assertTrue(ApplicationId::SHARED->isShared());
    }

    #[DataProvider('getFromStringData')]
    public function testFromStringWithValidValues(?string $input, ApplicationId $expected): void
    {
        $this->assertSame($expected, ApplicationId::fromString($input));
    }

    public function testFromStringWithInvalidValuesThrowsException(): void
    {
        $this->expectException(ValueError::class);

        ApplicationId::fromString('ACME');
    }

    public function testGetAccessibleDossierStatusesForPublic(): void
    {
        $this->assertMatchesSnapshot(ApplicationId::PUBLIC->getAccessibleDossierStatuses());
    }

    public function testGetAccessibleDossierStatusesForAdmin(): void
    {
        $this->assertMatchesSnapshot(ApplicationId::ADMIN->getAccessibleDossierStatuses());
    }

    public function testGetAccessibleDossierStatusesForPublicationApi(): void
    {
        $this->expectException(ApplicationIdException::class);
        ApplicationId::PUBLICATION_API->getAccessibleDossierStatuses();
    }

    public function testGetAccessibleDossierStatusesForWorker(): void
    {
        $this->expectException(ApplicationIdException::class);
        ApplicationId::WORKER->getAccessibleDossierStatuses();
    }

    /**
     * @return array<string,array{input:?string,expected:ApplicationId}>
     */
    public static function getFromStringData(): array
    {
        return [
            'all lower case' => [
                'input' => 'admin',
                'expected' => ApplicationId::ADMIN,
            ],
            'all upper case' => [
                'input' => 'PUBLIC',
                'expected' => ApplicationId::PUBLIC,
            ],
            'mixed cases' => [
                'input' => 'PUBlication_API',
                'expected' => ApplicationId::PUBLICATION_API,
            ],
            'null uses fallback' => [
                'input' => null,
                'expected' => ApplicationId::SHARED,
            ],
            'empty string uses fallback' => [
                'input' => '',
                'expected' => ApplicationId::SHARED,
            ],
        ];
    }
}
