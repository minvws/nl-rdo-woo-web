<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Workflow\Guard;

use Mockery;
use Psr\Log\NullLogger;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\Guard\DossierWorkflowGuard;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

class DossierWorkflowGuardTest extends UnitTestCase
{
    public function testGetSubscribedEvents(): void
    {
        $expectedSubscribedEvents = [
            'workflow.advice_workflow.guard.publish' => ['guardDossier'],
            'workflow.advice_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.advice_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.annual_report_workflow.guard.publish' => ['guardDossier'],
            'workflow.annual_report_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.annual_report_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.complaint_judgement_workflow.guard.publish' => ['guardDossier'],
            'workflow.complaint_judgement_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.complaint_judgement_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.covenant_workflow.guard.publish' => ['guardDossier'],
            'workflow.covenant_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.covenant_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.disposition_workflow.guard.publish' => ['guardDossier'],
            'workflow.disposition_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.disposition_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.investigation_report_workflow.guard.publish' => ['guardDossier'],
            'workflow.investigation_report_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.investigation_report_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.other_publication_workflow.guard.publish' => ['guardDossier'],
            'workflow.other_publication_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.other_publication_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.request_for_advice_workflow.guard.publish' => ['guardDossier'],
            'workflow.request_for_advice_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.request_for_advice_workflow.guard.schedule_publish' => ['guardDossier'],
            'workflow.woo_decision_workflow.guard.publish' => ['guardDossier'],
            'workflow.woo_decision_workflow.guard.publish_as_preview' => ['guardDossier'],
            'workflow.woo_decision_workflow.guard.schedule_publish' => ['guardDossier'],
        ];
        $subscribedEvents = DossierWorkflowGuard::getSubscribedEvents();

        self::assertEquals($expectedSubscribedEvents, $subscribedEvents);
    }

    public function testGuardDossierWithoutErrors(): void
    {
        $wooDecision = new WooDecision();

        $constraintViolationList = Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(0);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($wooDecision, null, [DossierValidationGroup::WORKFLOW_PUBLISH->value, Constraint::DEFAULT_GROUP])
            ->andReturn($constraintViolationList);

        $event = Mockery::mock(GuardEvent::class);
        $event->expects('getSubject')
            ->andReturn($wooDecision);
        $event->expects('getTransition->getName')
            ->times(2)
            ->andReturn(DossierStatusTransition::PUBLISH->value);

        $dossierWorkflowGuard = new DossierWorkflowGuard(new NullLogger(), $validator);
        $dossierWorkflowGuard->guardDossier($event);
    }

    public function testGuardDossierWithErrors(): void
    {
        $wooDecision = new WooDecision();

        $constraintViolation = Mockery::mock(ConstraintViolationInterface::class);
        $constraintViolation->expects('getMessage')
            ->andReturn('error message');
        $constraintViolation->expects('getPropertyPath')
            ->andReturn('property path');

        $constraintViolationList = Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(1);
        $constraintViolationList->expects('get')
            ->with(0)
            ->andReturn($constraintViolation);

        $validator = Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($wooDecision, null, [DossierValidationGroup::WORKFLOW_PUBLISH->value, Constraint::DEFAULT_GROUP])
            ->andReturn($constraintViolationList);

        $event = Mockery::mock(GuardEvent::class);
        $event->expects('getSubject')
            ->andReturn($wooDecision);
        $event->expects('getTransition->getName')
            ->times(2)
            ->andReturn(DossierStatusTransition::PUBLISH->value);
        $event->expects('setBlocked');

        $dossierWorkflowGuard = new DossierWorkflowGuard(new NullLogger(), $validator);
        $dossierWorkflowGuard->guardDossier($event);
    }
}
