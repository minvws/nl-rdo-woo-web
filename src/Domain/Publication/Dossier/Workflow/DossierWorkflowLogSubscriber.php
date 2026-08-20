<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Service\HistoryService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Transition;
use Webmozart\Assert\Assert;

use function sprintf;

final readonly class DossierWorkflowLogSubscriber implements EventSubscriberInterface
{
    use TransitionDossierStatusTrait;

    public function __construct(
        private LoggerInterface $logger,
        private HistoryService $historyService,
    ) {
    }

    /**
     * @return array<string,string|array{0:string,1:int}|list<array{0:string,1?:int}>>
     */
    public static function getSubscribedevents(): array
    {
        $subscribedEvents = [];
        foreach (self::getSubscribedWorkflows() as $subscribedDossierWorkflow) {
            $subscribedEvents[sprintf('workflow.%s.entered', $subscribedDossierWorkflow)] = ['log'];
        }

        return $subscribedEvents;
    }

    public function log(EnteredEvent $event): void
    {
        $dossier = $event->getSubject();
        Assert::isInstanceOf($dossier, AbstractDossier::class);

        $transition = $event->getTransition();
        Assert::isInstanceOf($transition, Transition::class);

        $oldState = $this->getOldState($transition);
        $newState = $this->getNewState($transition);

        if ($oldState === DossierStatus::NEW && $newState === DossierStatus::CONCEPT) {
            return;
        }

        if ($oldState === $newState) {
            return;
        }

        $this->historyService->addDossierEntry($dossier->getId(), 'dossier_state_' . $newState->value, [
            'old' => '%' . $oldState->value . '%',
            'new' => '%' . $newState->value . '%',
        ]);

        $this->logger->info('Dossier state changed', [
            'dossier' => $dossier->getId(),
            'oldState' => $oldState->value,
            'newState' => $newState->value,
        ]);
    }

    /**
     * @return list<value-of<DossierWorkflow>>
     */
    private static function getSubscribedWorkflows(): array
    {
        return DossierWorkflow::all();
    }
}
