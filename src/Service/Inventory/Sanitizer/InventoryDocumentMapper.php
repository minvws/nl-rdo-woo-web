<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\DocumentNumber;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

readonly class InventoryDocumentMapper
{
    public function __construct(
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private string $publicBaseUrl,
    ) {
    }

    /**
     * @return array<int, array<string>|string>
     */
    public function map(Document $document): array
    {
        /** @var WooDecision $dossier */
        $dossier = $document->getDossiers()->first();

        return [
            $document->getDocumentId() ?: '',
            $document->getDocumentNr(),
            $document->getFileInfo()->getName() ?: '',
            $document->getJudgement() ? $this->translator->trans('public.documents.judgment.short.' . $document->getJudgement()->value) : '',
            $document->getGrounds(),
            $document->getRemark() ?: '',
            implode("\n", $document->getLinks()),
            $this->publicBaseUrl . $this->urlGenerator->generate(
                'app_document_detail',
                [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                    'documentId' => $document->getDocumentNr(),
                ],
            ),
            $document->isSuspended() ? 'ja' : '',
            implode(';', $this->getRelatedDocumentIds($document)),
            implode(';', $this->getRelatedDocumentUrls($document)),
            $dossier->getTitle() ?? '',
        ];
    }

    /**
     * @return array<string>
     */
    private function getRelatedDocumentIds(Document $document): array
    {
        $dossier = $document->getDossiers()->first();
        Assert::isInstanceOf($dossier, WooDecision::class);
        $documentNumber = DocumentNumber::fromDossierAndDocument($dossier, $document);

        return $document->getRefersTo()->map(
            function (Document $referredDocument) use ($documentNumber): string {
                $referredDossier = $referredDocument->getDossiers()->first();
                Assert::isInstanceOf($referredDossier, WooDecision::class);

                $referredDocumentNumber = DocumentNumber::fromDossierAndDocument($referredDossier, $referredDocument);

                if ($documentNumber->getMatter() !== $referredDocumentNumber->getMatter()) {
                    return $referredDocumentNumber->matter . '_' . $referredDocumentNumber->id;
                }

                return $referredDocumentNumber->id;
            },
        )->toArray();
    }

    /**
     * @return array<string>
     */
    private function getRelatedDocumentUrls(Document $document): array
    {
        return $document->getRefersTo()->map(
            function (Document $referredDocument): string {
                $documentDossier = $referredDocument->getDossiers()->first();
                Assert::isInstanceOf($documentDossier, WooDecision::class);

                return $this->publicBaseUrl . $this->urlGenerator->generate(
                    'app_document_detail',
                    [
                        'prefix' => $documentDossier->getDocumentPrefix(),
                        'dossierId' => $documentDossier->getDossierNr(),
                        'documentId' => $referredDocument->getDocumentNr(),
                    ],
                );
            },
        )->toArray();
    }
}
