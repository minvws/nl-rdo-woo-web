<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Command;

use Admin\Command\UserCreate;
use Admin\Domain\Authentication\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Shared\Service\Totp;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function substr_count;

class UserCreateTest extends UnitTestCase
{
    private UserService&MockInterface $userService;
    private Totp&MockInterface $totp;
    private OrganisationRepository&MockInterface $organisationRepository;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->userService = Mockery::mock(UserService::class);
        $this->totp = Mockery::mock(Totp::class);
        $this->organisationRepository = Mockery::mock(OrganisationRepository::class);

        $this->commandTester = new CommandTester(new UserCreate(
            $this->userService,
            $this->totp,
            $this->organisationRepository,
        ));
    }

    public function testExecuteFailsWhenTenantHasNoOrganisation(): void
    {
        $this->organisationRepository->expects('oldest')
            ->andReturnNull();
        $this->organisationRepository->expects('getLimited')->never();

        $this->userService->expects('createUser')->never();

        $statusCode = $this->commandTester->execute(['email' => 'foo@bar.baz', 'name' => 'foo']);

        self::assertSame(UserCreate::FAILURE, $statusCode);
        self::assertStringContainsString('This tenant does not have any organisation yet!', $this->commandTester->getDisplay());
    }

    public function testExecuteNonInteractiveUsesOldestOrganisation(): void
    {
        $organisation = Mockery::mock(Organisation::class);

        $this->organisationRepository->expects('oldest')
            ->andReturn($organisation);
        $this->organisationRepository->expects('getLimited')->never();

        $this->expectUserCreated('foo', 'foo@bar.baz', Roles::ROLE_VIEW_ACCESS, $organisation);

        $statusCode = $this->commandTester->execute(
            ['email' => 'foo@bar.baz', 'name' => 'foo'],
            ['interactive' => false],
        );

        self::assertSame(UserCreate::SUCCESS, $statusCode);

        $display = $this->commandTester->getDisplay();
        self::assertStringContainsString('User foo@bar.baz created.', $display);
        self::assertMatchesRegularExpression('/^Password\s*: plain-password$/m', $display);
        self::assertMatchesRegularExpression('/^TOTP Token\s*: mfa-token$/m', $display);
        self::assertStringContainsString(' - recovery-code', $display);
    }

    public function testExecuteWithSuperAdminOptionCreatesSuperAdmin(): void
    {
        $organisation = Mockery::mock(Organisation::class);

        $this->organisationRepository->expects('oldest')
            ->andReturn($organisation);

        $this->expectUserCreated('foo', 'foo@bar.baz', Roles::ROLE_SUPER_ADMIN, $organisation);

        $statusCode = $this->commandTester->execute(
            ['email' => 'foo@bar.baz', 'name' => 'foo', '--super-admin' => true],
            ['interactive' => false],
        );

        self::assertSame(UserCreate::SUCCESS, $statusCode);
    }

    public function testExecuteInteractiveWithSingleOrganisationSkipsQuestion(): void
    {
        $organisation = Mockery::mock(Organisation::class);

        $this->organisationRepository->expects('oldest')
            ->andReturn($organisation);
        $this->organisationRepository->expects('getLimited')
            ->andReturn(new ArrayCollection([$organisation]));

        $this->expectUserCreated('foo', 'foo@bar.baz', Roles::ROLE_VIEW_ACCESS, $organisation);

        $statusCode = $this->commandTester->execute(['email' => 'foo@bar.baz', 'name' => 'foo']);

        self::assertSame(UserCreate::SUCCESS, $statusCode);
        self::assertStringNotContainsString('Please select your organisation', $this->commandTester->getDisplay());
    }

    public function testExecuteInteractiveAsksForOrganisation(): void
    {
        $organisationA = Mockery::mock(Organisation::class);
        $organisationA->allows('getName')
            ->andReturn('Organisation A');

        $organisationB = Mockery::mock(Organisation::class);
        $organisationB->allows('getName')
            ->andReturn('Organisation B');

        $this->organisationRepository->expects('oldest')
            ->andReturn($organisationA);
        $this->organisationRepository->expects('getLimited')
            ->andReturn(new ArrayCollection([$organisationA, $organisationB]));

        $this->expectUserCreated('foo', 'foo@bar.baz', Roles::ROLE_VIEW_ACCESS, $organisationB);

        $this->commandTester->setInputs(['Organisation B']);
        $statusCode = $this->commandTester->execute(['email' => 'foo@bar.baz', 'name' => 'foo']);

        self::assertSame(UserCreate::SUCCESS, $statusCode);

        $display = $this->commandTester->getDisplay();
        self::assertStringContainsString('Please select your organisation', $display);
        self::assertStringContainsString('You have selected organisation: Organisation B', $display);
    }

    public function testExecuteHandlesUniqueConstraintViolation(): void
    {
        $organisation = Mockery::mock(Organisation::class);

        $this->organisationRepository->expects('oldest')
            ->andReturn($organisation);

        $this->userService->expects('createUser')
            ->with('foo', 'foo@bar.baz', [Roles::ROLE_VIEW_ACCESS], $organisation)
            ->andThrow(Mockery::mock(UniqueConstraintViolationException::class));

        $statusCode = $this->commandTester->execute(
            ['email' => 'foo@bar.baz', 'name' => 'foo'],
            ['interactive' => false],
        );

        self::assertSame(UserCreate::FAILURE, $statusCode);

        $display = $this->commandTester->getDisplay();
        self::assertSame(1, substr_count($display, '[ERROR]'));
        self::assertStringContainsString('A user account with foo@bar.baz already exists', $display);
        self::assertStringContainsString("Please provide a unique email address using 'email' argument", $display);
    }

    private function expectUserCreated(string $name, string $email, string $role, Organisation $organisation): void
    {
        $user = Mockery::mock(User::class);
        $user->allows('getEmail')
            ->andReturn($email);
        $user->allows('getMfaToken')
            ->andReturn('mfa-token');
        $user->allows('getMfaRecovery')
            ->andReturn(['recovery-code']);

        $this->totp->allows('getTotpUri')
            ->with($user)
            ->andReturn('otpauth://totp/foo');

        $this->userService->expects('createUser')
            ->with($name, $email, [$role], $organisation)
            ->andReturn(['plainPassword' => 'plain-password', 'user' => $user]);
    }
}
