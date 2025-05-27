<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\UserService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserServiceTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private UserPasswordHasherInterface&MockInterface $passwordHasher;
    private TotpAuthenticatorInterface&MockInterface $totp;
    private LoggerInterface&MockInterface $logger;
    private AuditLogger $auditLogger;
    private TokenStorageInterface&MockInterface $tokenStorage;
    private UserService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->passwordHasher = \Mockery::mock(UserPasswordHasherInterface::class);
        $this->totp = \Mockery::mock(TotpAuthenticatorInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->auditLogger = \Mockery::mock(AuditLogger::class);
        $this->tokenStorage = \Mockery::mock(TokenStorageInterface::class);

        $this->service = new UserService(
            $this->entityManager,
            $this->passwordHasher,
            $this->totp,
            $this->logger,
            $this->auditLogger,
            $this->tokenStorage,
        );
    }

    public function testGet2faQrCodeImage(): void
    {
        $user = \Mockery::mock(User::class);
        $this->totp->expects('getQRContent')->with($user)->andReturn('fooBar');

        $this->assertMatchesTextSnapshot($this->service->get2faQrCodeImage($user));
    }
}
