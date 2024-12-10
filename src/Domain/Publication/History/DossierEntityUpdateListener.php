<?php

declare(strict_types=1);

namespace App\Domain\Publication\History;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierTypeWithPreview;
use App\Service\HistoryService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: AbstractDossier::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: AbstractDossier::class)]
final class DossierEntityUpdateListener
{
    /** @var array<array-key,History> */
    private array $historyEntries = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function preUpdate(AbstractDossier $dossier, PreUpdateEventArgs $args): void
    {
        $changes = $this->getUpdatedFields($args);
        if (count($changes) > 0) {
            $this->addHistoryEntry(
                $dossier,
                'dossier_updated',
                [
                    'changes' => $changes,
                ],
            );
        }

        if ($dossier->getPublicationDate() !== null && $args->hasChangedField('publicationDate')) {
            $this->addHistoryEntry(
                $dossier,
                'dossier_update_publication_date',
                [
                    'date' => $dossier->getPublicationDate()->format('d-m-Y'),
                ],
            );
        }

        if ($dossier instanceof DossierTypeWithPreview && $dossier->getPreviewDate() !== null && $args->hasChangedField('previewDate')) {
            $this->addHistoryEntry(
                $dossier,
                'dossier_update_preview_date',
                [
                    'date' => $dossier->getPreviewDate()->format('d-m-Y'),
                ],
            );
        }
    }

    /**
     * The postUpdate is used to persist the history entries. This is not possible to do directly in the preUpdate event
     * since that is triggered during a flush and no additional persist and/or flush actions can be executed at that
     * point. But the preUpdate is the only event that contains the changeset so it must be used to determine changes.
     *
     * So the changes are first determined in preUpdate and temporarily stored in $this->historyEntries.
     * In this postUpdate method they are persisted and flushed as this is now safely possible.
     *
     * See the restrictions for preUpdate documented here:
     * https://www.doctrine-project.org/projects/doctrine-orm/en/3.2/reference/events.html#preupdate
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postUpdate(AbstractDossier $dossier, PostUpdateEventArgs $event): void
    {
        if (count($this->historyEntries) === 0) {
            return;
        }

        foreach ($this->historyEntries as $entry) {
            $this->entityManager->persist($entry);
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<array-key,string>
     */
    private function getUpdatedFields(PreUpdateEventArgs $args): array
    {
        $changes = [];

        if ($args->hasChangedField('decisionDate')) {
            $changes[] = '%history.value.decision_date%';
        }

        if ($args->hasChangedField('title')) {
            $changes[] = '%history.value.title%';
        }

        if ($args->hasChangedField('summary')) {
            $changes[] = '%history.value.summary%';
        }

        return $changes;
    }

    /**
     * @param array<string, string|array<array-key,string>> $context
     */
    private function addHistoryEntry(AbstractDossier $dossier, string $contextKey, array $context): void
    {
        $history = new History();
        $history->setCreatedDt(new \DateTimeImmutable());
        $history->setType(HistoryService::TYPE_DOSSIER);
        $history->setIdentifier($dossier->getId());
        $history->setContextKey($contextKey);
        $history->setContext($context);
        $history->setSite(HistoryService::MODE_BOTH);

        $this->historyEntries[] = $history;
    }
}
