<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\AttachmentAndMainDocumentEntityTrait;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
use App\Entity\EntityWithFileInfo;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbstractMainDocumentRepository::class)]
#[ORM\Table(name: 'main_document')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'entity_type', type: 'string')]
#[ORM\DiscriminatorMap([
    'covenant_main_document' => CovenantDocument::class,
    'annual_report_main_document' => AnnualReportDocument::class,
    'investigation_report_main_document' => InvestigationReportDocument::class,
    'disposition_main_document' => DispositionDocument::class,
    'complaint_judgement_main_document' => ComplaintJudgementDocument::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMainDocument implements EntityWithFileInfo
{
    use AttachmentAndMainDocumentEntityTrait;

    abstract public function getDossier(): AbstractDossier&EntityWithMainDocument;
}
