<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Command;

use Admin\Command\UserReset;
use Admin\Domain\Authentication\UserService;
use Mockery;
use Mockery\MockInterface;
use Shared\Service\Security\User;
use Shared\Service\Security\UserRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UserResetTest extends UnitTestCase
{
    private UserService&MockInterface $userService;
    private UserRepository&MockInterface $userRepository;

    protected function setUp(): void
    {
        $this->userService = Mockery::mock(UserService::class);
        $this->userRepository = Mockery::mock(UserRepository::class);
    }

    public function testExecute(): void
    {
        $email = $this->getFaker()->safeEmail();
        $password = $this->getFaker()->password();
        $mfaToken = $this->getFaker()->slug(1);

        $user = Mockery::mock(User::class);
        $user->expects('getEmail')
            ->andReturn($email);
        $user->expects('getMfaToken')
            ->andReturn($mfaToken);
        $user->expects('getMfaRecovery')
            ->andReturn([$mfaToken]);

        $this->userRepository->expects('findOneBy')
            ->with(['email' => $email])
            ->andReturn($user);

        $this->userService->expects('resetPassword')
            ->with($user)
            ->andReturn($password);
        $this->userService->expects('resetTwoFactorAuth')
            ->with($user);

        $command = new UserReset($this->userService, $this->userRepository);
        $commandTester = new CommandTester($command);

        self::assertEquals(0, $commandTester->execute(['email' => $email]));
    }

    public function testExecuteUserNotFound(): void
    {
        $email = $this->getFaker()->safeEmail();

        $this->userRepository->expects('findOneBy')
            ->with(['email' => $email])
            ->andReturn(null);

        $command = new UserReset($this->userService, $this->userRepository);
        $commandTester = new CommandTester($command);

        self::assertEquals(1, $commandTester->execute(['email' => $email]));
    }
}
