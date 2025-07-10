<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Service\Security\UserRepository;
use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Reset extends Command
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:user:reset')
            ->setDescription('Reset user credentials')
            ->setHelp('Reset user credentials')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'Email of the user'),
            ]);
    }

    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = strval($input->getArgument('email'));
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (! $user) {
            $output->writeln("User <info>{$email}</info> not found.");

            return 1;
        }

        $plainPassword = $this->userService->resetCredentials($user, true, true);

        $output->writeln("User <info>{$user->getEmail()}</info> created.");
        $output->writeln("Password   : <info>{$plainPassword}</info>");
        $output->writeln("TOTP Token : <info>{$user->getMfaToken()}</info>");
        $output->writeln('TOTP Recovery codes: ');
        foreach ($user->getMfaRecovery() ?? [] as $code) {
            $output->writeln(" - <info>{$code}</info>");
        }

        return 0;
    }
}
