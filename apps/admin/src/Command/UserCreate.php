<?php

declare(strict_types=1);

namespace Admin\Command;

use Admin\Domain\Authentication\UserService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Shared\Domain\Organisation\Organisation;
use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Shared\Service\Totp;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

use function is_null;

#[AsCommand(name: 'woopie:user:create', description: 'Create a new user')]
class UserCreate extends Command
{
    public function __construct(
        protected UserService $userService,
        protected Totp $totp,
        protected EntityManagerInterface $doctrine,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Creates a new user')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'Email of the user'),
                new InputArgument('name', InputArgument::REQUIRED, 'Full name of user'),
                new InputOption('super-admin', 's', InputOption::VALUE_NONE, 'Super Admin user'),
            ]);
    }

    /**
     * @throws JsonException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('super-admin')) {
            $role = Roles::ROLE_SUPER_ADMIN;
        } else {
            $role = Roles::ROLE_VIEW_ACCESS;
        }

        // We assume that the created user is always part of the first organisation
        $organisation = $this->doctrine->getRepository(Organisation::class)->findAll()[0];

        $name = $input->getArgument('name');
        Assert::string($name);

        $email = $input->getArgument('email');
        Assert::string($email);

        try {
            ['plainPassword' => $plainPassword, 'user' => $user] = $this->userService->createUser(
                $name,
                $email,
                [$role],
                $organisation
            );
        } catch (UniqueConstraintViolationException) {
            $output->writeln("<error>A user account with email $email already exists</error>");
            $output->writeln("Please provide a unique email address using 'email' argument");

            return self::FAILURE;
        }

        $output->writeln("User <info>{$user->getEmail()}</info> created.");
        $output->writeln("Password   : <info>{$plainPassword}</info>");
        $output->writeln("TOTP URL : <info>{$this->totp->getTotpUri($user)}</info>");
        $output->writeln("TOTP Token : <info>{$user->getMfaToken()}</info>");
        $output->writeln('TOTP Recovery codes: ');
        foreach ($user->getMfaRecovery() ?? [] as $code) {
            $output->writeln(" - <info>{$code}</info>");
        }

        $this->printAnsiIfAvailable($user, $output);

        return self::SUCCESS;
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
