<?php

declare(strict_types=1);

namespace App\Form\Dossier\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use App\Form\YearMonthType;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return Covenant::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTitleField($builder);

        /** @var Covenant $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('date_from', YearMonthType::class, [
                'label' => 'global.date_from',
                'row_attr' => [
                    'data-fieldset' => 'date_from date_to',
                    'data-legend' => 'admin.dossiers.covenant.form.details.date_legend',
                    'data-required' => true,
                ],
                'required' => false,
                'placeholder' => 'global.date_from.placeholder',
                'day_mode' => YearMonthType::MODE_FROM,
                'property_path' => 'dateFrom',
                'dossier' => $dossier,
            ])
            ->add('date_to', YearMonthType::class, [
                'label' => 'global.date_to',
                'required' => false,
                'placeholder' => 'global.date_to.placeholder',
                YearMonthType::DAY_MODE => YearMonthType::MODE_TO,
                YearMonthType::MIN_YEARS => 10,
                YearMonthType::PLUS_YEARS => 5,
                YearMonthType::REVERSE => true,
                'property_path' => 'dateTo',
            ]);

        $this->addInternalReferenceField($builder);
        $this->addDepartmentsField($builder);
        $this->addSubjectField($builder);
        $this->addNewDossierFields($builder);
        $this->addSubmits($builder);
    }
}
