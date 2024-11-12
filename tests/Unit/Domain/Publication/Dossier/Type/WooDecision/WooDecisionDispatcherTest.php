<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\GenerateInquiryInventoryCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveInventoryAndDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateDecisionCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateInquiryLinksCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawAllDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Entity\WithdrawReason;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private WooDecisionDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new WooDecisionDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchCreateDossierCommand(): void
    {
        $id = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (RemoveInventoryAndDocumentsCommand $command) use ($id) {
                self::assertEquals($id, $command->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchRemoveInventoryAndDocumentsCommand($id);
    }

    public function testDispatchWithdrawAllDocumentsCommand(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $reason = WithdrawReason::INCORRECT_ATTACHMENT;
        $explanation = 'oops';

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (WithDrawAllDocumentsCommand $command) use ($wooDecision, $reason, $explanation) {
                self::assertEquals($wooDecision, $command->dossier);
                self::assertEquals($reason, $command->reason);
                self::assertEquals($explanation, $command->explanation);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchWithdrawAllDocumentsCommand($wooDecision, $reason, $explanation);
    }

    public function testDispatchUpdateDecisionCommand(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateDecisionCommand $command) use ($wooDecision) {
                self::assertEquals($wooDecision, $command->dossier);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateDecisionCommand($wooDecision);
    }

    public function testDispatchGenerateInquiryInventoryCommand(): void
    {
        $id = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (GenerateInquiryInventoryCommand $command) use ($id) {
                self::assertEquals($id, $command->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchGenerateInquiryInventoryCommand($id);
    }

    public function testDispatchUpdateInquiryLinksCommand(): void
    {
        $id = Uuid::v6();
        $caseNr = 'foo-123';
        $docIdsToAdd = [Uuid::v6(), Uuid::v6()];
        $docIdsToDelete = [Uuid::v6()];
        $dossierIdsToAdd = [Uuid::v6()];

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (UpdateInquiryLinksCommand $command) use ($id, $caseNr, $docIdsToAdd, $docIdsToDelete, $dossierIdsToAdd) {
                self::assertEquals($id, $command->getOrganisationId());
                self::assertEquals($caseNr, $command->getCaseNr());
                self::assertEquals($docIdsToAdd, $command->getDocIdsToAdd());
                self::assertEquals($docIdsToDelete, $command->getDocIdsToDelete());
                self::assertEquals($dossierIdsToAdd, $command->getDossierIdsToAdd());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchUpdateInquiryLinksCommand($id, $caseNr, $docIdsToAdd, $docIdsToDelete, $dossierIdsToAdd);
    }
}
