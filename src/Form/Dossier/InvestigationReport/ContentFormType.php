<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\InvestigationReport;

use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormBuilderTrait;
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
