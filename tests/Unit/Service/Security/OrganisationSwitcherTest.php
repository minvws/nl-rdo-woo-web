<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security;

use App\Entity\Organisation;
use App\Entity\User;
use App\Repository\OrganisationRepository;
use App\Roles;
use App\Service\Security\OrganisationSwitcher;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

class OrganisationSwitcherTest extends MockeryTestCase
{
    private User&MockInterface $user;
    private OrganisationRepository&MockInterface $organisationRepository;
    private RequestStack&MockInterface $requestStack;
    private OrganisationSwitcher $organisationSwitcher;
    private Session&MockInterface $session;

    public function setUp(): void
    {
        $this->user = \Mockery::mock(User::class);

        $this->organisationRepository = \Mockery::mock(OrganisationRepository::class);

        $this->session = \Mockery::mock(Session::class);

        $this->requestStack = \Mockery::mock(RequestStack::class);
        $this->requestStack->shouldReceive('getSession')->andReturns($this->session);

        $this->organisationSwitcher = new OrganisationSwitcher(
            $this->organisationRepository,
            $this->requestStack,
        );
    }

    public function testGetActiveOrganisationReturnsUserOrganisationForNormalUsers(): void
    {
        $organisation = \Mockery::mock(Organisation::class);

        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnFalse();
        $this->user->expects('hasRole')->with(Roles::ROLE_GLOBAL_ADMIN)->andReturnFalse();
        $this->user->expects('getOrganisation')->andReturn($organisation);

        $this->assertEquals(
            $organisation,
            $this->organisationSwitcher->getActiveOrganisation($this->user),
        );
    }

    public function testGetActiveOrganisationReturnsAndStoresDefaultOrganisationForSuperAdminWithoutSession(): void
    {
        $uuid = Uuid::v6();

        $organisationA = \Mockery::mock(Organisation::class);
        $organisationA->expects('getId')->andReturn($uuid);
        $organisationB = \Mockery::mock(Organisation::class);

        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->twice()->andReturnTrue();
        $this->organisationRepository->expects('getAllSortedByName')->andReturn([$organisationA, $organisationB]);

        $this->session->expects('has')->with('organisation')->andReturnFalse();
        $this->session->expects('set')->with('organisation', $uuid->toRfc4122());

        $this->assertEquals(
            $organisationA,
            $this->organisationSwitcher->getActiveOrganisation($this->user),
        );
    }

    public function testGetActiveOrganisationReturnsStoredOrganisationForSuperAdminFromSession(): void
    {
        $uuid = Uuid::v6();

        $organisation = \Mockery::mock(Organisation::class);

        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnTrue();

        $this->session->expects('has')->with('organisation')->andReturnTrue();
        $this->session->expects('get')->with('organisation')->andReturn($uuid->toRfc4122());

        $this->organisationRepository->expects('find')->with(\Mockery::on(
            static fn (Uuid $queryUuid) => $queryUuid->toRfc4122() === $uuid->toRfc4122()
        ))->andReturn($organisation);

        $this->assertEquals(
            $organisation,
            $this->organisationSwitcher->getActiveOrganisation($this->user),
        );
    }

    public function testGetActiveOrganisationReturnsDefaultOrganisationWhenOrganisationFromSessionCannotBeFound(): void
    {
        $uuid = Uuid::v6();

        $this->user->shouldReceive('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnTrue();

        $organisationA = \Mockery::mock(Organisation::class);
        $organisationB = \Mockery::mock(Organisation::class);
        $this->organisationRepository->expects('getAllSortedByName')->andReturn([$organisationA, $organisationB]);

        $this->session->expects('has')->with('organisation')->andReturnTrue();
        $this->session->expects('get')->with('organisation')->andReturn($uuid->toRfc4122());

        $this->organisationRepository->expects('find')->with(\Mockery::on(
            static fn (Uuid $queryUuid) => $queryUuid->toRfc4122() === $uuid->toRfc4122()
        ))->andReturnNull();

        $this->assertEquals(
            $organisationA,
            $this->organisationSwitcher->getActiveOrganisation($this->user),
        );
    }

    public function testGetActiveOrganisationThrowsExceptionWhenFallbackToDefaultOrganisationFails(): void
    {
        $uuid = Uuid::v6();

        $this->user->shouldReceive('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnTrue();

        $this->session->expects('has')->with('organisation')->andReturnTrue();
        $this->session->expects('get')->with('organisation')->andReturn($uuid->toRfc4122());

        $this->organisationRepository->expects('getAllSortedByName')->andReturn([]);

        $this->organisationRepository->expects('find')->with(\Mockery::on(
            static fn (Uuid $queryUuid) => $queryUuid->toRfc4122() === $uuid->toRfc4122()
        ))->andReturnNull();

        $this->expectException(\RuntimeException::class);

        $this->organisationSwitcher->getActiveOrganisation($this->user);
    }

    public function testGetOrganisationsReturnsOnlyTheUserOrganisationForNormalUsers(): void
    {
        $organisation = \Mockery::mock(Organisation::class);

        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnFalse();
        $this->user->expects('hasRole')->with(Roles::ROLE_GLOBAL_ADMIN)->andReturnFalse();
        $this->user->expects('getOrganisation')->andReturn($organisation);

        $this->assertEquals(
            [$organisation],
            $this->organisationSwitcher->getOrganisations($this->user),
        );
    }

    public function testGetOrganisationsReturnsAllOrganisationsForSuperAdmins(): void
    {
        $organisationA = \Mockery::mock(Organisation::class);
        $organisationB = \Mockery::mock(Organisation::class);

        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnTrue();
        $this->organisationRepository->expects('getAllSortedByName')->andReturn([$organisationA, $organisationB]);

        $this->assertEquals(
            [$organisationA, $organisationB],
            $this->organisationSwitcher->getOrganisations($this->user),
        );
    }

    public function testSwitchToOrganisationIsDeniedForNormalUsers(): void
    {
        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnFalse();
        $this->user->expects('hasRole')->with(Roles::ROLE_GLOBAL_ADMIN)->andReturnFalse();

        $organisation = \Mockery::mock(Organisation::class);

        $this->expectException(AccessDeniedException::class);

        $this->organisationSwitcher->switchToOrganisation($this->user, $organisation);
    }

    public function testSwitchToOrganisationIsAppliedForSuperAdmins(): void
    {
        $uuid = Uuid::v6();
        $organisation = \Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($uuid);

        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnTrue();
        $this->session->expects('set')->with('organisation', $uuid->toRfc4122());

        $this->organisationSwitcher->switchToOrganisation($this->user, $organisation);
    }

    public function testSwitchToOrganisationIsAppliedForGlobalAdmins(): void
    {
        $uuid = Uuid::v6();
        $organisation = \Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn($uuid);

        $this->user->expects('hasRole')->with(Roles::ROLE_SUPER_ADMIN)->andReturnFalse();
        $this->user->expects('hasRole')->with(Roles::ROLE_GLOBAL_ADMIN)->andReturnTrue();
        $this->session->expects('set')->with('organisation', $uuid->toRfc4122());

        $this->organisationSwitcher->switchToOrganisation($this->user, $organisation);
    }
}
