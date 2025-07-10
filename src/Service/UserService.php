<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Organisation\Organisation;
use App\Service\Security\Event\UserCreatedEvent;
use App\Service\Security\Event\UserDisableEvent;
use App\Service\Security\Event\UserEnableEvent;
use App\Service\Security\Event\UserResetEvent;
use App\Service\Security\Event\UserUpdatedEvent;
use App\Service\Security\Roles;
use App\Service\Security\User;
use App\Service\Security\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use Minvws\HorseBattery\HorseBattery;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class UserService
{
    protected HorseBattery $passwordGenerator;

    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TotpAuthenticatorInterface $totp,
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        $this->passwordGenerator = new HorseBattery();
    }

    public function resetCredentials(User $user, bool $resetPassword, bool $reset2fa): string
    {
        $plainPassword = '';

        if ($resetPassword) {
            $plainPassword = $this->passwordGenerator->generate(4);
            $hash = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hash);
            $user->setChangepwd(true);
        }

        if ($reset2fa) {
            $secret = $this->totp->generateSecret();
            $user->setMfaToken($secret);

            $recoveryCodes = [];
            for ($i = 0; $i !== 5; $i++) {
                $recoveryCodes[] = $this->passwordGenerator->generate(4);
            }
            $user->setMfaRecovery($recoveryCodes);
        }

        $this->userRepository->save($user, true);

        $this->logger->log('info', 'User credentials reset', [
            'user' => $user->getId(),
            'resetPassword' => $resetPassword,
            'reset2fa' => $reset2fa,
        ]);

        /** @var ?User $actor */
        $actor = $this->tokenStorage->getToken()?->getUser();

        $this->eventDispatcher->dispatch(
            new UserResetEvent(
                user: $user,
                actor: $actor,
                resetPassword: $resetPassword,
                resetTwoFactorAuth: $reset2fa,
            ),
        );

        return $plainPassword;
    }

    /**
     * @param array<string> $roles
     *
     * @return array{plainPassword: string, user: User}
     *
     * @throws \Minvws\HorseBattery\Exception\WordCountTooShort
     * @throws UniqueConstraintViolationException
     */
    public function createUser(string $name, string $email, array $roles, Organisation $organisation): array
    {
        // Canonicalize email address
        $encoding = mb_detect_encoding($email);
        $email = $encoding
            ? mb_convert_case($email, MB_CASE_LOWER, $encoding)
            : mb_convert_case($email, MB_CASE_LOWER);

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setOrganisation($organisation);

        $plainPassword = $this->passwordGenerator->generate(4);
        $hash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hash);

        $secret = $this->totp->generateSecret();
        $user->setMfaToken($secret);

        $recoveryCodes = [];
        for ($i = 0; $i != 5; $i++) {
            $recoveryCodes[] = $this->passwordGenerator->generate(4);
        }
        $user->setMfaRecovery($recoveryCodes);

        $user->setEnabled(true);
        $user->setRoles($roles);
        $user->setChangepwd(true);  // User must change password on first login

        $this->userRepository->save($user, true);

        $this->logger->log('info', 'User created', [
            'user' => $user->getId(),
            'roles' => $roles,
        ]);

        /** @var ?User $actor */
        $actor = $this->tokenStorage->getToken()?->getUser();

        $this->eventDispatcher->dispatch(
            new UserCreatedEvent(
                user: $user,
                actor: $actor,
                roles: $roles,
            ),
        );

        return [
            'plainPassword' => $plainPassword,
            'user' => $user,
        ];
    }

    public function get2faQrCode(User $user): string
    {
        return $this->totp->getQRContent($user);
    }

    public function get2faQrCodeImage(User $user): string
    {
        $qrContent = $this->totp->getQRContent($user);
        $builder = new Builder(
            writerOptions: [PngWriter::WRITER_OPTION_NUMBER_OF_COLORS => null],
            data: $qrContent,
        );

        return $builder->build()->getDataUri();
    }

    /**
     * This will update the roles of the target user. However, it will mask roles that
     * the current user (actor) is not allowed to modify.
     *
     * @param string[] $roles
     */
    public function updateRoles(LoggableUser $actor, User $oldUser, User $target, array $roles): void
    {
        // Fetch all the roles that the actor is allowed to change
        $allowedRoles = [];
        foreach ($actor->getRoles() as $role) {
            $allowedRoles = array_merge($allowedRoles, Roles::getRoleHierarchy($role));
        }

        // any roles that the user cannot modify (ie: not in the allowed roles), will be just added
        // to the roles as-is. This will effectively mask the roles that the actor is not allowed to
        // modify.
        $roles = array_merge($roles, array_diff($target->getRoles(), $allowedRoles));

        $target->setRoles($roles);
        $this->userRepository->save($target, true);

        $this->logger->log('info', 'User roles updated', [
            'user' => $target->getId(),
            'roles' => $roles,
        ]);

        $this->eventDispatcher->dispatch(
            new UserUpdatedEvent(
                oldUser: $oldUser,
                updatedUser: $target,
                actor: $actor,
            ),
        );
    }

    public function disable(User $user, LoggableUser $actor): void
    {
        $user->setEnabled(false);
        $this->userRepository->save($user, true);

        $this->eventDispatcher->dispatch(
            new UserDisableEvent(
                user: $user,
                actor: $actor,
            ),
        );
    }

    public function enable(User $user, LoggableUser $actor): void
    {
        $user->setEnabled(true);
        $this->userRepository->save($user, true);

        $this->eventDispatcher->dispatch(
            new UserEnableEvent(
                user: $user,
                actor: $actor,
            ),
        );
    }
}
