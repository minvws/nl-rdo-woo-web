<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow\Guard;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceWorkflow;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportWorkflow;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementWorkflow;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantWorkflow;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionWorkflow;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportWorkflow;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationWorkflow;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceWorkflow;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionWorkflow;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
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
     * @return array<string, array<array-key, string>>
     */
    public static function getSubscribedEvents(): array
    {
        $subscribedEvents = [];
        foreach (self::getSubscribedWorkflows() as $subscribedDossierWorkflow) {
            foreach (self::getSubscribedTransitions() as $subscribedTransition) {
                $subscribedEvents[sprintf('workflow.%s.guard.%s', $subscribedDossierWorkflow, $subscribedTransition->value)] = ['guardDossier'];
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
     * @return array<array-key, string>
     */
    private static function getSubscribedWorkflows(): array
    {
        return [
            AdviceWorkflow::ADVICE_WORKFLOW_NAME,
            AnnualReportWorkflow::ANNUAL_REPORT_WORKFLOW_NAME,
            ComplaintJudgementWorkflow::COMPLAINT_JUDGEMENT_WORKFLOW_NAME,
            CovenantWorkflow::COVENANT_WORKFLOW_NAME,
            DispositionWorkflow::DISPOSITION_WORKFLOW_NAME,
            InvestigationReportWorkflow::INVESTIGATION_REPORT_WORKFLOW_NAME,
            OtherPublicationWorkflow::OTHER_PUBLICATION_WORKFLOW_NAME,
            RequestForAdviceWorkflow::REQUEST_FOR_ADVICE_WORKFLOW_NAME,
            WooDecisionWorkflow::WOO_DECISION_WORKFLOW_NAME,
        ];
    }

    /**
     * @return array<array-key, DossierStatusTransition>
     */
    private static function getSubscribedTransitions(): array
    {
        return [
            DossierStatusTransition::PUBLISH,
            DossierStatusTransition::PUBLISH_AS_PREVIEW,
            DossierStatusTransition::SCHEDULE_PUBLISH,
        ];
    }

    /**
     * @return array<array-key, string>
     */
    private function getValidationGroupsFromEvent(GuardEvent $event): array
    {
        $transitionName = $event->getTransition()->getName();
        $dossierStatusTransition = DossierStatusTransition::from($transitionName);
        $dossierValidationGroups = DossierValidationGroup::getForWorkflowTransitions($dossierStatusTransition);

        return EnumHelper::getStringValues($dossierValidationGroups);
    }
}
