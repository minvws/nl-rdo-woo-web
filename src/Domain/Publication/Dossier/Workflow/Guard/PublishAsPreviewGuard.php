<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow\Guard;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierTypeWithPreview;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

class PublishAsPreviewGuard
{
    #[AsEventListener(event: 'workflow.guard')]
    public function guardPublicationAsPreview(GuardEvent $event): void
    {
        if ($event->getTransition()->getName() !== DossierStatusTransition::PUBLISH_AS_PREVIEW->value) {
            return;
        }

        /** @var AbstractDossier $dossier */
        $dossier = $event->getSubject();
        if (! $dossier instanceof DossierTypeWithPreview) {
            $event->setBlocked(true, 'A dossier of a type that does not support preview cannot be published as preview');

            return;
        }

        if ($dossier->getPreviewDate() === null || $dossier->getPreviewDate() > new DateTimeImmutable('now midnight')) {
            $event->setBlocked(true, 'A dossier with an empty or future preview date cannot be published as preview');
        }
    }
}
