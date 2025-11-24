<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\User;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Shared\Command\User\Create;
use Shared\Domain\Organisation\Organisation;
use Shared\Service\Totp;
use Shared\Service\UserService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTest extends UnitTestCase
{
    private Create $command;
    private UserService&MockInterface $userService;
    private Totp&MockInterface $totp;
    private EntityManagerInterface&MockInterface $entityManager;

    protected function setUp(): void
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
