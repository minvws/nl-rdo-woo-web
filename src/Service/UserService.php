<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Organisation;
use App\Entity\User;
use App\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\AuditUser;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\ResetCredentialsLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserCreatedLogEvent;
use Minvws\HorseBattery\HorseBattery;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This class handles user management.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserService
{
    protected EntityManagerInterface $doctrine;
    protected UserPasswordHasherInterface $passwordHasher;
    protected TotpAuthenticatorInterface $totp;
    protected HorseBattery $passwordGenerator;
    protected LoggerInterface $logger;
    protected AuditLogger $auditLogger;
    protected TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $doctrine,
        UserPasswordHasherInterface $passwordHasher,
        TotpAuthenticatorInterface $totp,
        LoggerInterface $logger,
        AuditLogger $auditLogger,
        TokenStorageInterface $tokenStorage
    ) {
        $this->doctrine = $doctrine;
        $this->passwordHasher = $passwordHasher;
        $this->totp = $totp;

        $this->passwordGenerator = new HorseBattery();
        $this->logger = $logger;
        $this->auditLogger = $auditLogger;
        $this->tokenStorage = $tokenStorage;
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
            for ($i = 0; $i != 5; $i++) {
                $recoveryCodes[] = $this->passwordGenerator->generate(4);
            }
            $user->setMfaRecovery($recoveryCodes);
        }

        $this->doctrine->persist($user);
        $this->doctrine->flush();

        $this->logger->log('info', 'User credentials reset', [
            'user' => $user->getId(),
            'resetPassword' => $resetPassword,
            'reset2fa' => $reset2fa,
        ]);

        /** @var LoggableUser|null $loggedInUser */
        $loggedInUser = $this->tokenStorage->getToken()?->getUser() ?? null;
        if ($loggedInUser === null) {
            $loggedInUser = new AuditUser('cli user', 'system', [], 'system@localhost');
        }
        /** @var LoggableUser $loggedInUser */
        $this->auditLogger->log((new ResetCredentialsLogEvent())
            ->asUpdate()
            ->withActor($loggedInUser)
            ->withTarget($user)
            ->withSource('woo')
            ->withData([
                'user_id' => $user->getAuditId(),
                'password_reset' => $resetPassword,
                '2fa_reset' => $reset2fa,
            ]));

        return $plainPassword;
    }

    /**
     * @param array<string> $roles
     *
     * @return array{plainPassword: string, user: User}
     *
     * @throws \Minvws\HorseBattery\Exception\WordCountTooShort
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

        $this->doctrine->persist($user);
        $this->doctrine->flush();

        $this->logger->log('info', 'User created', [
            'user' => $user->getId(),
            'roles' => $roles,
        ]);

        /** @var LoggableUser|null $loggedInUser */
        $loggedInUser = $this->tokenStorage->getToken()?->getUser() ?? null;
        if ($loggedInUser === null) {
            $loggedInUser = new AuditUser('cli user', 'system', [], 'system@localhost');
        }
        /** @var LoggableUser $loggedInUser */
        $this->auditLogger->log((new UserCreatedLogEvent())
            ->asCreate()
            ->withActor($loggedInUser)
            ->withTarget($user)
            ->withSource('woo')
            ->withData([
                'user_id' => $user->getAuditId(),
                'roles' => $roles,
            ]));

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

        return Builder::create()
            ->data($qrContent)
            ->build()->getDataUri();
    }

    /**
     * This will update the roles of the target user. However, it will mask roles that
     * the current user (actor) is not allowed to modify.
     *
     * @param string[] $roles
     */
    public function updateRoles(LoggableUser $actor, User $target, array $roles): void
    {
        $oldRoles = $target->getRoles();

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
        $this->doctrine->persist($target);
        $this->doctrine->flush();

        $this->logger->log('info', 'User roles updated', [
            'user' => $target->getId(),
            'roles' => $roles,
        ]);

        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($actor)
            ->withTarget($target)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_USERDATA)
            ->withData([
                'user_id' => $target->getAuditId(),
                'old' => [
                    'roles' => $oldRoles,
                ],
                'new' => [
                    'roles' => $target->getRoles(),
                ],
            ]));
    }
}
