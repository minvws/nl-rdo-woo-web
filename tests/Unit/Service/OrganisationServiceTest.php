<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Organisation\Event\OrganisationCreatedEvent;
use Shared\Domain\Organisation\Event\OrganisationUpdatedEvent;
use Shared\Domain\Organisation\Organisation;
use Shared\Service\OrganisationService;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OrganisationServiceTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private LoggerInterface&MockInterface $logger;
    private TokenStorageInterface&MockInterface $tokenStorage;
    private OrganisationService $organisationService;
    private EventDispatcherInterface&MockInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $this->eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $this->organisationService = new OrganisationService(
            $this->entityManager,
            $this->logger,
            $this->tokenStorage,
            $this->eventDispatcher,
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

        $this->eventDispatcher->expects('dispatch')->with(\Mockery::on(
            static function (OrganisationCreatedEvent $event) use ($user, $organisation): bool {
                self::assertEquals($user, $event->actor);
                self::assertEquals($organisation, $event->organisation);

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

        $this->eventDispatcher->expects('dispatch')->with(\Mockery::on(
            static function (OrganisationUpdatedEvent $event) use ($user, $organisation): bool {
                self::assertEquals($user, $event->actor);
                self::assertEquals($organisation, $event->organisation);
                self::assertEquals([], $event->changes);

                return true;
            }
        ));

        $this->organisationService->update($organisation);
    }
}
