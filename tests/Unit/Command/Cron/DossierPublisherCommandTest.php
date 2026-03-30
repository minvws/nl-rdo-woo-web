<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Mockery;
use Shared\Command\Cron\DossierPublisherCommand;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\MessageBusInterface;

class DossierPublisherCommandTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $dossier1 = Mockery::mock(WooDecision::class);
        $dossier2 = Mockery::mock(WooDecision::class);
        $dossier3 = Mockery::mock(WooDecision::class);

        $repository = Mockery::mock(DossierRepository::class);
        $repository->expects('findDossiersPendingPublication')
            ->andReturn([
                $dossier1,
                $dossier2,
                $dossier3,
            ]);

        $publisher = Mockery::mock(MessageBusInterface::class);
        $publisher->expects('dispatch')
            ->with(Mockery::on(static function (UpdateDossierPublicationCommand $updateDossierPublicationCommand) use ($dossier1): bool {
                return $updateDossierPublicationCommand->dossier === $dossier1;
            }));
        $publisher->expects('dispatch')
            ->with(Mockery::on(static function (UpdateDossierPublicationCommand $updateDossierPublicationCommand) use ($dossier2): bool {
                return $updateDossierPublicationCommand->dossier === $dossier2;
            }));
        $publisher->expects('dispatch')
            ->with(Mockery::on(static function (UpdateDossierPublicationCommand $updateDossierPublicationCommand) use ($dossier3): bool {
                return $updateDossierPublicationCommand->dossier === $dossier3;
            }));

        $command = new DossierPublisherCommand($repository, $publisher);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertEquals($command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Done', $output);
    }
}
