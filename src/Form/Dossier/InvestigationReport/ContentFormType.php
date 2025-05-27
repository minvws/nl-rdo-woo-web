<?php

declare(strict_types=1);

namespace App\Form\Dossier\InvestigationReport;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\FormBuilderInterface;

class ContentFormType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return InvestigationReport::class;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSummaryField($builder);
        $this->addDocumentField($builder);
        $this->addSubmits($builder);
    }
}
