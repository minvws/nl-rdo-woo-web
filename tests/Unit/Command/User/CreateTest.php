<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\User;

use App\Command\User\Create;
use App\Entity\Organisation;
use App\Service\Totp;
use App\Service\UserService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTest extends MockeryTestCase
{
    private Create $command;
    private UserService&MockInterface $userService;
    private Totp&MockInterface $totp;
    private EntityManagerInterface&MockInterface $entityManager;

    public function setUp(): void
    {
        $this->userService = \Mockery::mock(UserService::class);
        $this->totp = \Mockery::mock(Totp::class);
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);

        $this->command = new Create(
            $this->userService,
            $this->totp,
            $this->entityManager,
        );
    }

    public function testExecuteHandlesUniqueConstraintViolation(): void
    {
        $input = \Mockery::mock(InputInterface::class);

        $output = \Mockery::mock(OutputInterface::class);
        $output->shouldReceive('writeln');

        $organisation = \Mockery::mock(Organisation::class);

        $input->shouldReceive('getOption')->with('super-admin')->andReturnTrue();
        $input->shouldReceive('getArgument')->with('name')->andReturn('foo');
        $input->shouldReceive('getArgument')->with('email')->andReturn('foo@bar.baz');

        $this->entityManager->expects('getRepository->findAll')->andReturn([
            $organisation,
        ]);

        $this->userService->expects('createUser')->andThrow(\Mockery::mock(UniqueConstraintViolationException::class));

        self::assertEquals(1, $this->command->execute($input, $output));
    }
}
