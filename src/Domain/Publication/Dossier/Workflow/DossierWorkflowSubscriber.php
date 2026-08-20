<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DossierService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Transition;
use Webmozart\Assert\Assert;

use function sprintf;

final readonly class DossierWorkflowSubscriber implements EventSubscriberInterface
{
    use TransitionDossierStatusTrait;

    public function __construct(
        private DossierService $dossierService,
        private DossierDispatcher $dossierDispatcher,
    ) {
    }

    /**
     * @return array<string,string|array{0:string,1:int}|list<array{0:string,1?:int}>>
     */
    public static function getSubscribedEvents(): array
    {
        $subscribedEvents = [];
        foreach (self::getSubscribedWorkflows() as $subscribedDossierWorkflow) {
            $subscribedEvents[sprintf('workflow.%s.entered', $subscribedDossierWorkflow)] = ['handleEntityUpdate'];
            $subscribedEvents[sprintf('workflow.%s.completed', $subscribedDossierWorkflow)] = ['synchronizeArtifacts'];
        }

        return $subscribedEvents;
    }

    public function handleEntityUpdate(EnteredEvent $event): void
    {
        $transition = $event->getTransition();
        Assert::isInstanceOf($transition, Transition::class);

        $newState = $transition->getTos()[0] ?? null;
        Assert::string($newState);

        if ($newState === DossierStatus::DELETED->value) {
            return;
        }

        $dossier = $event->getSubject();
        Assert::isInstanceOf($dossier, AbstractDossier::class);

        $this->dossierService->validateCompletion($dossier);

        $this->dossierDispatcher->dispatchSynchronizeArtifactsCommand($dossier);
    }

    public function synchronizeArtifacts(CompletedEvent $event): void
    {
        $dossier = $event->getSubject();
        Assert::isInstanceOf($dossier, AbstractDossier::class);

        if (! $dossier instanceof WooDecision) {
            return;
        }

        if ($dossier->getStatus()->isPubliclyAvailable()) {
            return;
        }

        $transition = $event->getTransition();
        Assert::isInstanceOf($transition, Transition::class);

        $oldState = $this->getOldState($transition);
        $newState = $this->getNewState($transition);

        if ($newState === $oldState) {
            return;
        }

        $this->dossierDispatcher->dispatchSynchronizeArtifactsCommand($dossier);
    }

    /**
     * @return list<value-of<DossierWorkflow>>
     */
    private static function getSubscribedWorkflows(): array
    {
        return DossierWorkflow::all();
    }
}
