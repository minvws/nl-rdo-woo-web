<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Service\Security\User;
use App\Service\Security\UserRepository;
use App\Service\Totp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class View extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Totp $totp,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:user:view')
            ->setDescription('Retrieves the user 2fa token')
            ->setHelp('Retrieves the user 2fa token')
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

        $table = new Table($output);
        $table->setHeaders(['Email', 'Roles', 'TOTP Token', 'TOTP Recovery codes']);

        $table->addRow([
            $user->getEmail(),
            join(',', $user->getRoles()),
            $user->getMfaToken(),
            join("\n", $user->getMfaRecovery() ?? []),
        ]);
        $table->setVertical();
        $table->render();

        $this->printQrCode($user, $output);

        return 0;
    }

    protected function printQrCode(User $user, OutputInterface $output): void
    {
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
