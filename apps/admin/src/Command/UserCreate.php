<?php

declare(strict_types=1);

namespace Admin\Command;

use Admin\Domain\Authentication\UserService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use JsonException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Shared\Service\Totp;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

use function is_null;
use function sprintf;

#[AsCommand(name: 'woopie:user:create', description: 'Create a new user', help: 'Creates a new user')]
class UserCreate extends Command
{
    public function __construct(
        protected UserService $userService,
        protected Totp $totp,
        protected OrganisationRepository $organisationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'Email of the user'),
                new InputArgument('name', InputArgument::REQUIRED, 'Full name of user'),
                new InputOption('super-admin', 's', InputOption::VALUE_NONE, 'Super Admin user'),
            ]);
    }

    /**
     * @throws JsonException
     */
    public function __invoke(InputInterface $input, SymfonyStyle $io): int
    {
        if ($input->getOption('super-admin')) {
            $role = Roles::ROLE_SUPER_ADMIN;
        } else {
            $role = Roles::ROLE_VIEW_ACCESS;
        }

        $organisation = $this->askOrganisation($input, $io);
        if ($organisation === false) {
            return self::FAILURE;
        }

        $name = $input->getArgument('name');
        Assert::string($name);

        $email = $input->getArgument('email');
        Assert::string($email);

        try {
            ['plainPassword' => $plainPassword, 'user' => $user] = $this->userService->createUser(
                $name,
                $email,
                [$role],
                $organisation,
            );
        } catch (UniqueConstraintViolationException) {
            $io->error([
                sprintf('A user account with %s already exists', $email),
                "Please provide a unique email address using 'email' argument",
            ]);

            return self::FAILURE;
        }

        $io->writeln("User <info>{$user->getEmail()}</info> created.");
        $io->writeln("Password   : <info>{$plainPassword}</info>");
        $io->writeln("TOTP URL : <info>{$this->totp->getTotpUri($user)}</info>");
        $io->writeln("TOTP Token : <info>{$user->getMfaToken()}</info>");
        $io->writeln('TOTP Recovery codes: ');
        foreach ($user->getMfaRecovery() ?? [] as $code) {
            $io->writeln(" - <info>{$code}</info>");
        }

        $this->printAnsiIfAvailable($user, $io);

        return self::SUCCESS;
    }

    protected function askOrganisation(InputInterface $input, SymfonyStyle $io): false|Organisation
    {
        $oldestOrganisation = $this->organisationRepository->oldest();
        if ($oldestOrganisation === null) {
            $io->error('This tenant does not have any organisation yet! Aborting...');

            return false;
        }

        if (! $input->isInteractive()) {
            return $oldestOrganisation;
        }

        $organisations = $this->organisationRepository->getLimited();
        if ($organisations->count() === 1) {
            return $oldestOrganisation;
        }

        $question = new ChoiceQuestion(
            'Please select your organisation:',
            $organisations->map(static fn (Organisation $organisation): string => $organisation->getName())->toArray(),
            0,
        );
        $question->setErrorMessage('Organisation %s is invalid.');

        $chosenOrganisation = $io->askQuestion($question);
        $organisation = $organisations->findFirst(
            static fn (int $i, Organisation $organisation): bool => $organisation->getName() === $chosenOrganisation,
        );

        Assert::isInstanceOf($organisation, Organisation::class);

        $io->writeln(sprintf('You have selected organisation: %s', $organisation->getName()));
        $io->newLine();

        return $organisation;
    }

    protected function printAnsiIfAvailable(User $user, SymfonyStyle $io): void
    {
        $io->newLine();

        $finder = new ExecutableFinder();
        $path = $finder->find('qrencode');
        if (is_null($path)) {
            $io->writeln('qrencode not found, skipping QR code');
            $io->writeln('If you want to display a QR code, install qrencode and use woopie:user:view to display the QR code.');

            return;
        }

        $uri = $this->totp->getTotpUri($user);
        $process = new Process([$path, '-t', 'ANSIUTF8', $uri]);
        $process->run();
        $io->writeln($process->getOutput());
    }
}
