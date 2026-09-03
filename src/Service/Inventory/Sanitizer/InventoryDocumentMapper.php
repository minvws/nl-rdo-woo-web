<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

use function implode;

readonly class InventoryDocumentMapper
{
    public function __construct(
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private string $publicBaseUrl,
    ) {
    }

    /**
     * @return array<int, array<array-key, string>|string>
     */
    public function map(Document $document): array
    {
        $dossier = $document->getDossiers()->first();
        Assert::isInstanceOf($dossier, WooDecision::class);

        return [
            $document->getDocumentId()->toString(),
            $document->getDocumentNumber(),
            $document->getFileInfo()->getName() ?: '',
            $document->getJudgement() ? $this->translator->trans('public.documents.judgment.short.' . $document->getJudgement()->value) : '',
            $document->getGrounds(),
            $document->getRemark() ?: '',
            implode("\n", $document->getLinks()),
            $this->publicBaseUrl . $this->urlGenerator->generate(
                'app_document_detail',
                [
                    'documentPrefix' => $dossier->getDocumentPrefix(),
                    'dossierNumber' => $dossier->getDossierNumber(),
                    'documentNumber' => $document->getDocumentNumber(),
                ],
            ),
            $document->isSuspended() ? 'ja' : '',
            implode(';', $this->getRelatedDocumentNumbers($document)),
            implode(';', $this->getRelatedDocumentUrls($document)),
            (string) $dossier->getTitle(),
        ];
    }

    /**
     * @return array<array-key,string>
     */
    private function getRelatedDocumentNumbers(Document $document): array
    {
        $dossier = $document->getDossiers()->first();
        Assert::isInstanceOf($dossier, WooDecision::class);

        return $document->getRefersTo()->map(
            static function (Document $referredDocument): string {
                return $referredDocument->getDocumentNumber();
            },
        )->toArray();
    }

    /**
     * @return array<array-key, string>
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
                        'documentPrefix' => $documentDossier->getDocumentPrefix(),
                        'dossierNumber' => $documentDossier->getDossierNumber(),
                        'documentNumber' => $referredDocument->getDocumentNumber(),
                    ],
                );
            },
        )->toArray();
    }
}
