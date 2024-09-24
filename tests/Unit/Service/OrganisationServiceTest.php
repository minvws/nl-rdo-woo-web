<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Organisation;
use App\Entity\User;
use App\Service\OrganisationService;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\GeneralLogEvent;
use MinVWS\AuditLogger\Events\Logging\OrganisationChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\OrganisationCreatedLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OrganisationServiceTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private LoggerInterface&MockInterface $logger;
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private TokenStorageInterface&MockInterface $tokenStorage;
    private OrganisationService $organisationService;
    private AuditLogger $auditLogger;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->tokenStorage = \Mockery::mock(TokenStorageInterface::class);

        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->organisationService = new OrganisationService(
            $this->entityManager,
            $this->logger,
            $this->auditLogger,
            $this->tokenStorage,
        );

        parent::setUp();
    }

    public function testCreate(): void
    {
        $organisation = new Organisation();
        $organisation->setName('foo');

        $this->entityManager->expects('persist')->with($organisation);
        $this->entityManager->expects('flush');

        $this->logger->expects('log');

        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('audit-id-foo');
        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $this->tokenStorage->expects('getToken')->andReturn($token);

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            static function (OrganisationCreatedLogEvent $event): bool {
                self::assertEquals('audit-id-foo', $event->getLogData()['user_id']);
                self::assertEquals(GeneralLogEvent::AC_CREATE, $event->getLogData()['action_code']);

                return true;
            }
        ));

        $this->organisationService->create($organisation);
    }

    public function testUpdate(): void
    {
        $organisation = new Organisation();
        $organisation->setName('foo');

        $this->entityManager->expects('getUnitOfWork->computeChangeSets');
        $this->entityManager->expects('getUnitOfWork->getEntityChangeSet')->with($organisation)->andReturn([]);
        $this->entityManager->expects('persist')->with($organisation);
        $this->entityManager->expects('flush');

        $this->logger->expects('log');

        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('audit-id-foo');
        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $this->tokenStorage->expects('getToken')->andReturn($token);

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            static function (OrganisationChangeLogEvent $event): bool {
                self::assertEquals('audit-id-foo', $event->getLogData()['user_id']);
                self::assertEquals(GeneralLogEvent::AC_UPDATE, $event->getLogData()['action_code']);

                return true;
            }
        ));

        $this->organisationService->update($organisation);
    }
}
