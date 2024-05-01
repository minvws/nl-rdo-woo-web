<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Workflow\Guard;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierTypeWithPreview;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

final class PublishAsPreviewGuard implements EventSubscriberInterface
{
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

        if ($dossier->getPreviewDate() === null || $dossier->getPreviewDate() > new \DateTimeImmutable('now midnight')) {
            $event->setBlocked(true, 'A dossier with an empty or future preview date cannot be published as preview');
        }

        if (! $dossier->isCompleted()) {
            $event->setBlocked(true, 'A dossier that is not completed cannot be published as preview');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.guard' => ['guardPublicationAsPreview'],
        ];
    }
}
