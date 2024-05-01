<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DossierWizard;

use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use App\Service\DossierWizard\DossierWizardHelper;
use App\Service\DossierWizard\WizardStatusFactory;
use App\Service\HistoryService;
use App\Service\Inventory\InventoryService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DossierWizardHelperTest extends MockeryTestCase
{
    private WooDecision&MockInterface $dossier;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DossierService&MockInterface $dossierService;
    private DossierWizardHelper $helper;
    private DossierPublisher&MockInterface $dossierPublisher;
    private InventoryService&MockInterface $inventoryService;
    private WizardStatusFactory&MockInterface $wizardStatusFactory;
    private HistoryService&MockInterface $historyService;
    private MessageBusInterface&MockInterface $messageBus;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->dossierPublisher = \Mockery::mock(DossierPublisher::class);
        $this->inventoryService = \Mockery::mock(InventoryService::class);
        $this->wizardStatusFactory = \Mockery::mock(WizardStatusFactory::class);
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

        $this->helper = new DossierWizardHelper(
            $this->dossierService,
            $this->dossierPublisher,
            $this->inventoryService,
            $this->wizardStatusFactory,
            $this->historyService,
            $this->dossierWorkflowManager,
            $this->messageBus,
        );
    }

    public function testPublishExecutesDirectPublicationWhenPossible(): void
    {
        $this->dossierService->expects('updateHistory')->with($this->dossier);
        $this->dossierService->expects('handleEntityUpdate')->with($this->dossier);

        $this->dossierPublisher->expects('canPublish')->with($this->dossier)->andReturnTrue();
        $this->dossierPublisher->expects('publish')->with($this->dossier);

        $this->helper->publish($this->dossier);
    }

    public function testPublisherExecutesPublicationAsPreviewWhenDirectPublicationIsNotPossible(): void
    {
        $this->dossierService->expects('updateHistory')->with($this->dossier);
        $this->dossierService->expects('handleEntityUpdate')->with($this->dossier);

        $this->dossierPublisher->expects('canPublish')->with($this->dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($this->dossier)->andReturnTrue();
        $this->dossierPublisher->expects('publishAsPreview')->with($this->dossier);

        $this->helper->publish($this->dossier);
    }

    public function testPublishSchedulesPublicationWhenPublicationAndPublicationAsPreviewAreNotPossible(): void
    {
        $this->dossierService->expects('updateHistory')->with($this->dossier);
        $this->dossierService->expects('handleEntityUpdate')->with($this->dossier);

        $this->dossierPublisher->expects('canPublish')->with($this->dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($this->dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($this->dossier)->andReturnTrue();
        $this->dossierPublisher->expects('schedulePublication')->with($this->dossier);

        $this->helper->publish($this->dossier);
    }

    public function testPublishUpdatesPublicationSettingsWhenNoPublicationIsPossible(): void
    {
        $this->dossierService->expects('updateHistory')->with($this->dossier);
        $this->dossierService->expects('handleEntityUpdate')->with($this->dossier);

        $this->dossierPublisher->expects('canPublish')->with($this->dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canPublishAsPreview')->with($this->dossier)->andReturnFalse();
        $this->dossierPublisher->expects('canSchedulePublication')->with($this->dossier)->andReturnFalse();

        $this->helper->publish($this->dossier);
    }

    public function testDispatchForwardsCommandToMessageBus(): void
    {
        $command = new \stdClass();

        $this->messageBus->expects('dispatch')->with($command)->andReturns(new Envelope(new \stdClass()));

        $this->helper->dispatch($command);
    }

    public function testDeleteThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $this->dossier->expects('getId')->andReturn(Uuid::v6());

        $this->dossierWorkflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::DELETE)
            ->andReturnFalse();

        $this->expectException(DossierWorkflowException::class);

        $this->helper->delete($this->dossier);
    }

    public function testDeleteDispatchesCommand(): void
    {
        $dossierId = Uuid::v6();
        $this->dossier->expects('getId')->andReturn($dossierId);

        $this->dossierWorkflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::DELETE)
            ->andReturnTrue();

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (DeleteDossierCommand $command) use ($dossierId): bool {
                return $command->getUuid() === $dossierId;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->helper->delete($this->dossier);
    }

    public function testUpdateDecisionDocument(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::UPDATE_DECISION_DOCUMENT);

        $this->dossierService->expects('updateDecisionDocument')->with($upload, $this->dossier);

        $this->helper->updateDecisionDocument($this->dossier, $upload);
    }

    public function testUpdateDecision(): void
    {
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::UPDATE_DECISION);

        $this->dossierService->expects('updateDecision')->with($this->dossier);

        $this->helper->updateDecision($this->dossier);
    }

    public function testUpdateInventory(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);
        $form = \Mockery::mock(FormInterface::class);
        $form->expects('get->getData')->andReturn($upload);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $this->dossierService->expects('processInventory')->with($upload, $this->dossier);

        $this->helper->updateInventory($this->dossier, $form);
    }

    public function testConfirmInventoryUpdate(): void
    {
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $this->dossierService->expects('confirmInventoryUpdate')->with($this->dossier);

        $this->helper->confirmInventoryUpdate($this->dossier);
    }

    public function testRejectInventoryUpdate(): void
    {
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $this->dossierService->expects('rejectInventoryUpdate')->with($this->dossier);

        $this->helper->rejectInventoryUpdate($this->dossier);
    }

    public function testRemovedInventory(): void
    {
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $this->inventoryService->expects('removeInventories')->with($this->dossier);
        $this->dossierService->expects('validateCompletion')->with($this->dossier);

        $this->helper->removeInventory($this->dossier);
    }
}
