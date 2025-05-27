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
use Webmozart\Assert\Assert;

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

        if ($this->isFieldUpdated($args, 'publicationDate')) {
            $date = $dossier->getPublicationDate();
            Assert::notNull($date);

            $this->addHistoryEntry(
                $dossier,
                'dossier_update_publication_date',
                [
                    'date' => $date->format('d-m-Y'),
                ],
            );
        }

        if ($dossier instanceof DossierTypeWithPreview && $this->isFieldUpdated($args, 'previewDate')) {
            $date = $dossier->getPreviewDate();
            Assert::notNull($date);

            $this->addHistoryEntry(
                $dossier,
                'dossier_update_preview_date',
                [
                    'date' => $date->format('d-m-Y'),
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
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
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

        if ($this->isFieldUpdated($args, 'decisionDate')) {
            $changes[] = '%history.value.decision_date%';
        }

        if ($this->isFieldUpdated($args, 'title')) {
            $changes[] = '%history.value.title%';
        }

        if ($this->isFieldUpdated($args, 'summary')) {
            $changes[] = '%history.value.summary%';
        }

        return $changes;
    }

    /**
     * This method will return false when the old value of the given field was NULL or an empty string, assuming an
     * initial value is being set, which is not an update.
     * Do not use this method on fields that can be reset to NULL or an empty string by the user.
     */
    private function isFieldUpdated(PreUpdateEventArgs $args, string $fieldName): bool
    {
        if (! $args->hasChangedField($fieldName)) {
            return false;
        }

        return $args->getOldValue($fieldName) !== null && $args->getOldValue($fieldName) !== '';
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
