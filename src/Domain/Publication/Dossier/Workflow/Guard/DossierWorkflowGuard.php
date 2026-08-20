<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow\Guard;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflow;
use Shared\Service\EnumHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Webmozart\Assert\Assert;

use function sprintf;

final readonly class DossierWorkflowGuard implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @return array<string,array<array-key,string>>
     */
    public static function getSubscribedEvents(): array
    {
        $subscribedEvents = [];
        foreach (self::getSubscribedWorkflows() as $subscribedDossierWorkflow) {
            foreach (self::getSubscribedTransitions() as $subscribedTransition) {
                $subscribedEvents[sprintf('workflow.%s.guard.%s', $subscribedDossierWorkflow, $subscribedTransition)] = ['guardDossier'];
            }
        }

        return $subscribedEvents;
    }

    public function guardDossier(GuardEvent $event): void
    {
        $this->logger->debug('DossierWorkflowGuard triggered', [
            'transitionName' => $event->getTransition()->getName(),
        ]);

        $dossier = $event->getSubject();
        Assert::isInstanceOf($dossier, AbstractDossier::class);

        $validationGroups = $this->getValidationGroupsFromEvent($event);
        $validationGroups[] = Constraint::DEFAULT_GROUP;

        $violations = $this->validator->validate($dossier, null, $validationGroups);

        if ($violations->count() > 0) {
            $violation = $violations->get(0);

            $message = (string) $violation->getMessage();
            $propertyPath = $violation->getPropertyPath();

            $this->logger->debug('DossierWorkflowGuard transition blocked', [
                'message' => $message,
                'propertyPath' => $propertyPath,
            ]);

            $event->setBlocked(true, $message);
        }
    }

    /**
     * @return list<value-of<DossierWorkflow>>
     */
    private static function getSubscribedWorkflows(): array
    {
        return DossierWorkflow::all();
    }

    /**
     * @return list<value-of<DossierStatusTransition>>
     */
    private static function getSubscribedTransitions(): array
    {
        return [
            DossierStatusTransition::PUBLISH->value,
            DossierStatusTransition::PUBLISH_AS_PREVIEW->value,
            DossierStatusTransition::SCHEDULE_PUBLISH->value,
        ];
    }

    /**
     * @return array<array-key,string>
     */
    private function getValidationGroupsFromEvent(GuardEvent $event): array
    {
        $transitionName = $event->getTransition()->getName();
        $dossierStatusTransition = DossierStatusTransition::from($transitionName);
        $dossierValidationGroups = DossierValidationGroup::getForWorkflowTransitions($dossierStatusTransition);

        return EnumHelper::getStringValues($dossierValidationGroups);
    }
}
