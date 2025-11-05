<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Command\CreateDossierCommand;
use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use App\Domain\Publication\Dossier\DossierDispatcher;
use App\Service\Security\User;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DossierDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private Security&MockInterface $security;
    private DossierDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->security = \Mockery::mock(Security::class);

        $this->dispatcher = new DossierDispatcher(
            $this->messageBus,
            $this->security,
        );
    }

    public function testDispatchCreateDossierCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CreateDossierCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchCreateDossierCommand($dossier);
    }

    public function testDispatchUpdateDossierDetailsCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierDetailsCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateDossierDetailsCommand($dossier);
    }

    public function testDispatchUpdateDossierContentCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierContentCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateDossierContentCommand($dossier);
    }

    public function testDispatchUpdateDossierPublicationCommand(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDossierPublicationCommand $command) use ($dossier) {
                self::assertEquals($dossier, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateDossierPublicationCommand($dossier);
    }

    public function testDispatchDeleteDossierCommand(): void
    {
        $id = Uuid::v6();

        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getUserIdentifier')->andReturn($userId = 'foo-bar');
        $user->shouldReceive('getEmail')->andReturn($email = 'foo@bar.baz');
        $user->shouldReceive('getName')->andReturn($name = 'Foo Bar');
        $user->shouldReceive('getRoles')->andReturn($roles = ['foo']);

        $this->security->expects('getUser')->andReturns($user);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DeleteDossierCommand $command) use ($id, $userId, $email, $name, $roles) {
                self::assertEquals($id, $command->dossierId);
                self::assertEquals($userId, $command->auditUserDetails->getAuditId());
                self::assertEquals($name, $command->auditUserDetails->getName());
                self::assertEquals($email, $command->auditUserDetails->getEmail());
                self::assertEquals($roles, $command->auditUserDetails->getRoles());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchDeleteDossierCommand($id);
    }
}
