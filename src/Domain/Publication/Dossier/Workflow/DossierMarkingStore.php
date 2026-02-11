<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Webmozart\Assert\Assert;

use function key;

final class DossierMarkingStore implements MarkingStoreInterface
{
    /**
     * @param AbstractDossier $subject
     */
    public function getMarking(object $subject): Marking
    {
        Assert::isInstanceOf($subject, AbstractDossier::class);

        return new Marking([$subject->getStatus()->value => 1]);
    }

    /**
     * @param AbstractDossier $subject
     * @param array<array-key, mixed> $context
     */
    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        unset($context);

        Assert::isInstanceOf($subject, AbstractDossier::class);

        $place = key($marking->getPlaces()) ?? '';
        $status = DossierStatus::from($place);

        $subject->setStatus($status);
    }
}
