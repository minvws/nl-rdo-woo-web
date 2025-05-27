<?php

declare(strict_types=1);

namespace App\Form\Dossier\AnnualReport;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use App\Form\YearType;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return AnnualReport::class;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AnnualReport $dossier */
        $dossier = $builder->getData();

        $this->addTitleField($builder);

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
            ])
        ;

        $this->addInternalReferenceField($builder);
        $this->addDepartmentsField($builder);
        $this->addSubjectField($builder);
        $this->addNewDossierFields($builder);
        $this->addSubmits($builder);
    }
}
