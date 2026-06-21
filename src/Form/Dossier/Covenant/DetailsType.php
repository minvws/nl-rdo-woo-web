<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\Covenant;

use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormFactory;
use Shared\Form\YearMonthType;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    public function __construct(
        private readonly DossierFormFactory $dossierFormFactory,
    ) {
    }

    public function getDataClass(): string
    {
        return Covenant::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dossierForm = $this->dossierFormFactory->for($builder);
        $dossierForm->addTitleField();

        $dossier = $dossierForm->getDossier();

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

        $dossierForm->addInternalReferenceField();
        $dossierForm->addDepartmentsField();
        $dossierForm->addSubjectField();
        $dossierForm->addDossierNrField();
        $dossierForm->addDocumentPrefixField();
        $dossierForm->addSubmits();
    }
}
