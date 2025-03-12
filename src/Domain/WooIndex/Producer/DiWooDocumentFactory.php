<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\WooIndex\Tooi\InformatieCategorie;
use App\Domain\WooIndex\Tooi\Ministerie;
use App\Domain\WooIndex\Tooi\SoortHandeling;
use Carbon\Carbon;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class DiWooDocumentFactory
{
    public function fromDocument(AbstractDossier $dossier, Document|AbstractAttachment|AbstractMainDocument $entity): DiWooDocument
    {
        $documentDate = $this->getCreatiedatum($entity, $dossier);

        return new DiWooDocument(
            creatiedatum: $documentDate,
            publisher: Ministerie::mnre1025,
            officieleTitel: $this->getOfficieleTitel($entity),
            informatieCategorie: $this->mapDossierTypeToInformatieCategorie($dossier->getType()),
            documentHandeling: new DocumentHandeling(
                soortHandeling: SoortHandeling::c_e1ec3050e,
                atTime: $documentDate,
            ),
        );
    }

    private function getCreatiedatum(Document|AbstractAttachment|AbstractMainDocument $entity, AbstractDossier $dossier): Carbon
    {
        $date = match (true) {
            $entity instanceof Document => $entity->getDocumentDate() ?? $dossier->getPublicationDate(),
            $entity instanceof AbstractAttachment,
            $entity instanceof AbstractMainDocument => $entity->getFormalDate(),
        };

        Assert::notNull($date);

        return Carbon::instance($date);
    }

    private function getOfficieleTitel(EntityWithFileInfo $entity): string
    {
        $officieleTitel = $entity->getFileInfo()->getName();
        Assert::notNull($officieleTitel);

        return $officieleTitel;
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
            DossierType::OTHER_PUBLICATION => InformatieCategorie::c_816e508d,
            DossierType::ADVICE => InformatieCategorie::c_99a836c7,
        };
    }
}
