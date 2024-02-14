<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Entity\Organisation;
use App\Entity\User;
use App\Service\Totp;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Create extends Command
{
    protected UserService $userService;
    protected Totp $totp;
    protected EntityManagerInterface $doctrine;

    public function __construct(UserService $userService, Totp $totp, EntityManagerInterface $doctrine)
    {
        parent::__construct();

        $this->userService = $userService;
        $this->totp = $totp;
        $this->doctrine = $doctrine;
    }

    protected function configure(): void
    {
        $this->setName('woopie:user:create')
            ->setDescription('Create a new user')
            ->setHelp('Creates a new user')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'Email of the user'),
                new InputArgument('name', InputArgument::REQUIRED, 'Full name of user'),
                new InputOption('admin', 'a', InputOption::VALUE_NONE, 'Admin user'),
                new InputOption('super-admin', 's', InputOption::VALUE_NONE, 'Super Admin user'),
            ]);
    }

    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('super-admin')) {
            $role = ['ROLE_SUPER_ADMIN'];
        } elseif ($input->getOption('admin')) {
            $role = ['ROLE_ADMIN'];
        } else {
            $role = ['ROLE_BALIE'];
        }

        // We assume that the created user is always part of the first organisation
        $organisation = $this->doctrine->getRepository(Organisation::class)->findAll()[0];

        ['plainPassword' => $plainPassword, 'user' => $user] = $this->userService->createUser(
            strval($input->getArgument('name')),
            strval($input->getArgument('email')),
            $role,
            $organisation
        );

        $output->writeln("User <info>{$user->getEmail()}</info> created.");
        $output->writeln("Password   : <info>{$plainPassword}</info>");
        $output->writeln("TOTP URL : <info>{$this->totp->getTotpUri($user)}</info>");
        $output->writeln("TOTP Token : <info>{$user->getMfaToken()}</info>");
        $output->writeln('TOTP Recovery codes: ');
        foreach ($user->getMfaRecovery() ?? [] as $code) {
            $output->writeln(" - <info>{$code}</info>");
        }

        $this->printAnsiIfAvailable($user, $output);

        return 0;
    }

    protected function printAnsiIfAvailable(User $user, OutputInterface $output): void
    {
        $output->writeln('');

        $finder = new ExecutableFinder();
        $path = $finder->find('qrencode');
        if (is_null($path)) {
            $output->writeln('qrencode not found, skipping QR code');
            $output->writeln('If you want to display a QR code, install qrencode and use woopie:user:view to display the QR code.');

            return;
        }

        $uri = $this->totp->getTotpUri($user);
        $process = new Process([$path, '-t', 'ANSIUTF8', $uri]);
        $process->run();
        $output->writeln($process->getOutput());
    }
}
