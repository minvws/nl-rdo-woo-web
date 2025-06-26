<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer\Mapper;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\WooIndex\Producer\DiWooDocument;
use App\Domain\WooIndex\Producer\DocumentHandeling;
use App\Domain\WooIndex\Producer\Repository\RawUrlDto;
use App\Domain\WooIndex\Tooi\InformatieCategorie;
use App\Domain\WooIndex\Tooi\Ministerie;
use App\Domain\WooIndex\Tooi\SoortHandeling;
use Carbon\CarbonImmutable;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final readonly class DiWooDocumentMapper
{
    public function __construct(
        private IsPartOfMapper $isPartOfMapper,
        private HasPartsMapper $hasPartsMapper,
    ) {
    }

    public function fromRawUrl(RawUrlDto $rawUrl): DiWooDocument
    {
        return new DiWooDocument(
            creatiedatum: CarbonImmutable::instance($rawUrl->documentDate),
            publisher: Ministerie::mnre1025,
            officieleTitel: $rawUrl->documentFileName,
            informatieCategorie: $this->mapDossierTypeToInformatieCategorie($rawUrl->dossierType),
            documentHandeling: new DocumentHandeling(
                soortHandeling: SoortHandeling::c_641ecd76,
                atTime: CarbonImmutable::instance($rawUrl->documentDate),
            ),
            isPartOf: $this->isPartOfMapper->fromRawUrl($rawUrl),
            hasParts: $this->hasPartsMapper->fromRawUrl($rawUrl),
        );
    }

    private function mapDossierTypeToInformatieCategorie(DossierType $type): InformatieCategorie
    {
        return match ($type) {
            DossierType::COVENANT => InformatieCategorie::c_8fc2335c,
            DossierType::WOO_DECISION => InformatieCategorie::c_3baef532,
            DossierType::ANNUAL_REPORT => InformatieCategorie::c_c6cd1213,
            DossierType::INVESTIGATION_REPORT => InformatieCategorie::c_fdaee95e,
            DossierType::DISPOSITION => InformatieCategorie::c_46a81018,
            DossierType::COMPLAINT_JUDGEMENT => InformatieCategorie::c_a870c43d,
            DossierType::OTHER_PUBLICATION => InformatieCategorie::c_aab6bfc7,
            DossierType::ADVICE, DossierType::REQUEST_FOR_ADVICE => InformatieCategorie::c_99a836c7,
        };
    }
}
