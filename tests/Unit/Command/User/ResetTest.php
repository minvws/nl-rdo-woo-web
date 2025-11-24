<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\User;

use Mockery\MockInterface;
use Shared\Command\User\Reset;
use Shared\Service\Security\User;
use Shared\Service\Security\UserRepository;
use Shared\Service\UserService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ResetTest extends UnitTestCase
{
    private UserService&MockInterface $userService;
    private UserRepository&MockInterface $userRepository;

    protected function setUp(): void
    {
        $this->userService = \Mockery::mock(UserService::class);
        $this->userRepository = \Mockery::mock(UserRepository::class);
    }

    public function testExecute(): void
    {
        $email = $this->getFaker()->safeEmail();
        $password = $this->getFaker()->password();
        $mfaToken = $this->getFaker()->slug(1);

        $user = \Mockery::mock(User::class);
        $user->expects('getEmail')
            ->once()
            ->andReturn($email);
        $user->expects('getMfaToken')
            ->once()
            ->andReturn($mfaToken);
        $user->expects('getMfaRecovery')
            ->once()
            ->andReturn([$mfaToken]);

        $this->userRepository->shouldReceive('findOneBy')
            ->once()
            ->with(['email' => $email])
            ->andReturn($user);

        $this->userService->expects('resetPassword')
            ->once()
            ->with($user)
            ->andReturn($password);
        $this->userService->expects('resetTwoFactorAuth')
            ->once()
            ->with($user);

        $command = new Reset($this->userService, $this->userRepository);
        $commandTester = new CommandTester($command);

        self::assertEquals(0, $commandTester->execute(['email' => $email]));
    }

    public function testExecuteUserNotFound(): void
    {
        $email = $this->getFaker()->safeEmail();

        $this->userRepository->shouldReceive('findOneBy')
            ->once()
            ->with(['email' => $email])
            ->andReturn(null);

        $command = new Reset($this->userService, $this->userRepository);
        $commandTester = new CommandTester($command);

        self::assertEquals(1, $commandTester->execute(['email' => $email]));
    }
}
