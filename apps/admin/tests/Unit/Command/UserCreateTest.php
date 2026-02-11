<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Command;

use Admin\Command\UserCreate;
use Admin\Domain\Authentication\UserService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Service\Totp;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserCreateTest extends UnitTestCase
{
    private UserCreate $command;
    private UserService&MockInterface $userService;
    private Totp&MockInterface $totp;
    private EntityManagerInterface&MockInterface $entityManager;

    protected function setUp(): void
    {
        $this->userService = Mockery::mock(UserService::class);
        $this->totp = Mockery::mock(Totp::class);
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);

        $this->command = new UserCreate(
            $this->userService,
            $this->totp,
            $this->entityManager,
        );
    }

    public function testExecuteHandlesUniqueConstraintViolation(): void
    {
        $input = Mockery::mock(InputInterface::class);

        $output = Mockery::mock(OutputInterface::class);
        $output->expects('writeln')
            ->twice();

        $organisation = Mockery::mock(Organisation::class);

        $input->expects('getOption')
            ->with('super-admin')
            ->andReturnTrue();
        $input->expects('getArgument')
            ->with('name')
            ->andReturn('foo');
        $input->expects('getArgument')
            ->with('email')
            ->andReturn('foo@bar.baz');

        $this->entityManager->expects('getRepository->findAll')
            ->andReturn([$organisation]);

        $this->userService->expects('createUser')
            ->andThrow(Mockery::mock(UniqueConstraintViolationException::class));

        self::assertEquals($this->command::FAILURE, $this->command->execute($input, $output));
    }
}
