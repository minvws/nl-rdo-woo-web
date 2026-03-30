<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Workflow\Guard;

use DateTimeImmutable;
use Psr\Log\NullLogger;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\Guard\DossierWorkflowGuard;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

class DossierWorkflowGuardTest extends SharedWebTestCase
{
    public function testGuardDossierPasses(): void
    {
        $wooDecision = WooDecisionFactory::new()
            ->scheduled()
            ->create([
                'dateFrom' => new DateTimeImmutable(),
                'dateTo' => new DateTimeImmutable('+1 week'),
                'previewDate' => new DateTimeImmutable('-1 week'),
                'publicationDate' => new DateTimeImmutable('-1 week'),
            ]);

        $validator = self::getContainer()->get(ValidatorInterface::class);

        $guardEvent = new GuardEvent($wooDecision, new Marking([]), new Transition(DossierStatusTransition::PUBLISH->value, [], []));

        $dossierWorkflowGuard = new DossierWorkflowGuard(new NullLogger(), $validator);
        $dossierWorkflowGuard->guardDossier($guardEvent);

        self::assertFalse($guardEvent->isBlocked());
    }

    public function testGuardDossierBlocked(): void
    {
        $wooDecision = WooDecisionFactory::new()
            ->concept()
            ->create();

        $validator = self::getContainer()->get(ValidatorInterface::class);

        $guardEvent = new GuardEvent($wooDecision, new Marking([]), new Transition(DossierStatusTransition::PUBLISH->value, [], []));

        $dossierWorkflowGuard = new DossierWorkflowGuard(new NullLogger(), $validator);
        $dossierWorkflowGuard->guardDossier($guardEvent);

        self::assertTrue($guardEvent->isBlocked());
    }
}
