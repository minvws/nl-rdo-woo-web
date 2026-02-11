<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Shared\Form\YearMonthType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return WooDecision::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WooDecision $dossier */
        $dossier = $builder->getData();

        $this->addTitleField($builder);
        $builder
            ->add('date_from', YearMonthType::class, [
                'label' => 'global.date_from',
                'row_attr' => [
                    'data-fieldset' => 'date_from date_to',
                    'data-legend' => 'admin.dossiers.decision.date_from_legend',
                    'data-required' => false,
                ],
                'required' => false,
                'placeholder' => 'global.date_from.placeholder',
                YearMonthType::DAY_MODE => YearMonthType::MODE_FROM,
                'property_path' => 'dateFrom',
                'dossier' => $dossier,
            ])
            ->add('date_to', YearMonthType::class, [
                'label' => 'global.date_to',
                'required' => false,
                'placeholder' => 'kies eindmaand',
                YearMonthType::DAY_MODE => YearMonthType::MODE_TO,
                YearMonthType::REVERSE => true,
                'property_path' => 'dateTo',
            ]);

        $this->addDepartmentsField($builder);
        $this->addSubjectField($builder);

        $builder
            ->add('publication_reason', EnumType::class, [
                'label' => 'publication.dossier.description.category.title',
                'class' => PublicationReason::class,
                'expanded' => true,
                'required' => true,
            ]);

        $this->addInternalReferenceField($builder);
        $this->addDossierNrField($builder);
        $this->addDocumentPrefixField($builder);
        $this->addSubmits($builder);
    }
}
