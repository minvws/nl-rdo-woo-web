<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\AnnualReport;

use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormFactory;
use Shared\Form\YearType;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    public function __construct(
        private readonly DossierFormFactory $dossierFormFactory,
    ) {
    }

    public function getDataClass(): string
    {
        return AnnualReport::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dossierForm = $this->dossierFormFactory->for($builder);
        $dossier = $dossierForm->getDossier();

        $dossierForm->addTitleField();

        $builder
            ->add('year', YearType::class, [
                'label' => 'global.year',
                'row_attr' => [
                    'data-fieldset' => 'year',
                    'data-legend' => 'admin.dossiers.annual-report.form.details.date_legend',
                    'data-required' => true,
                ],
                'required' => true,
                'placeholder' => $dossier->getDateFrom() === null ? 'global.year.placeholder' : false,
                'property_path' => 'dateFrom',
                'min_years' => 9,
                'plus_years' => 2,
            ]);

        $dossierForm->addInternalReferenceField();
        $dossierForm->addDepartmentsField();
        $dossierForm->addSubjectField();
        $dossierForm->addDossierNrField();
        $dossierForm->addDocumentPrefixField();
        $dossierForm->addSubmits();
    }
}
