<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History;

use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_values;

readonly class HistoryWooDecisionOriginResolver
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    /**
     * @param array<array-key, History> $entries
     *
     * @return array<string, HistoryWooDecisionOrigin|null>
     */
    public function resolve(array $entries): array
    {
        $originsByDossierId = $this->findOrigins($this->getDossierIds($entries));

        $origins = [];
        foreach ($entries as $entry) {
            $origins[$entry->getId()->toString()] = $this->findOrigin($entry, $originsByDossierId);
        }

        return $origins;
    }

    /**
     * @param array<array-key, History> $entries
     *
     * @return list<string>
     */
    private function getDossierIds(array $entries): array
    {
        $dossierIds = [];
        foreach ($entries as $entry) {
            $dossierId = $this->getDossierId($entry);
            if ($dossierId === null) {
                continue;
            }

            $dossierIds[$dossierId] = $dossierId;
        }

        return array_values($dossierIds);
    }

    /**
     * @param list<string> $dossierIds
     *
     * @return array<string, HistoryWooDecisionOrigin>
     */
    private function findOrigins(array $dossierIds): array
    {
        if ($dossierIds === []) {
            return [];
        }

        $origins = [];
        foreach ($this->wooDecisionRepository->findBy(['id' => $dossierIds]) as $wooDecision) {
            $origins[$wooDecision->getId()->toString()] = new HistoryWooDecisionOrigin(
                DossierReference::fromEntity($wooDecision),
                $wooDecision->getStatus()->isPubliclyAvailable(),
            );
        }

        return $origins;
    }

    /**
     * @param array<string, HistoryWooDecisionOrigin> $originsByDossierId
     */
    private function findOrigin(History $entry, array $originsByDossierId): ?HistoryWooDecisionOrigin
    {
        $dossierId = $this->getDossierId($entry);
        if ($dossierId === null) {
            return null;
        }

        if (! array_key_exists($dossierId, $originsByDossierId)) {
            return null;
        }

        return $originsByDossierId[$dossierId];
    }

    private function getDossierId(History $entry): ?string
    {
        $context = $entry->getContext();
        if (! array_key_exists(History::CONTEXT_ORIGIN_WOO_DECISION_ID, $context)) {
            return null;
        }

        $dossierId = $context[History::CONTEXT_ORIGIN_WOO_DECISION_ID];
        Assert::string($dossierId);

        return $dossierId;
    }
}
