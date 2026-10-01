<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Contracts\Translation\TranslatorInterface;

use function implode;

readonly class InventoryDocumentMapper
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<int, array<array-key, string>|string>
     */
    public function map(
        Document $document,
        WooDecision $dossier,
        InventoryDocumentUrlStrategyInterface $urlStrategy,
    ): array {
        $documentUrl = $urlStrategy->generateDocumentUrl($document, $dossier);

        return [
            $document->getDocumentId()->toString(),
            $document->getDocumentNumber()->toString(),
            $document->getFileInfo()->getName() ?: '',
            $document->getJudgement() ? $this->translator->trans('public.documents.judgment.short.' . $document->getJudgement()->value) : '',
            $document->getGrounds(),
            $document->getRemark() ?: '',
            implode("\n", $document->getLinks()),
            $documentUrl,
            $document->isSuspended() ? 'ja' : '',
            implode(';', $this->getRelatedDocumentNumbers($document)),
            implode(';', $this->getRelatedDocumentUrls($document, $dossier, $urlStrategy)),
            (string) $dossier->getTitle(),
        ];
    }

    /**
     * @return array<array-key,string>
     */
    private function getRelatedDocumentNumbers(Document $document): array
    {
        return $document->getRefersTo()->map(
            static function (Document $referredDocument): string {
                return $referredDocument->getDocumentNumber()->toString();
            },
        )->toArray();
    }

    /**
     * @return array<array-key, string>
     */
    private function getRelatedDocumentUrls(
        Document $document,
        WooDecision $dossier,
        InventoryDocumentUrlStrategyInterface $urlStrategy,
    ): array {
        return $document->getRefersTo()->map(
            static fn (Document $referredDocument): string => $urlStrategy->generateRelatedDocumentUrl(
                $referredDocument,
                $dossier,
            ),
        )->toArray();
    }
}
