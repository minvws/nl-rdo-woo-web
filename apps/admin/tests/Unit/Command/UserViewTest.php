<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Command;

use Admin\Command\UserView;
use Mockery;
use Shared\Service\Security\User;
use Shared\Service\Security\UserRepository;
use Shared\Service\Totp;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UserViewTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $email = $this->getFaker()->safeEmail();
        $mfaToken = $this->getFaker()->slug(1);

        $user = Mockery::mock(User::class);
        $user->expects('getEmail')
            ->andReturn($email);
        $user->expects('getRoles')
            ->andReturn([]);
        $user->expects('getMfaToken')
            ->andReturn($mfaToken);
        $user->expects('getMfaRecovery')
            ->andReturn([$mfaToken]);

        $userRepository = Mockery::mock(UserRepository::class);
        $userRepository->expects('findOneBy')
            ->with(['email' => $email])
            ->andReturn($user);

        $totp = Mockery::mock(Totp::class);

        $command = new UserView($userRepository, $totp);
        $commandTester = new CommandTester($command);

        self::assertEquals($command::SUCCESS, $commandTester->execute(['email' => $email]));
    }
}
