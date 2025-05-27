<?php

declare(strict_types=1);

namespace App\Form\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use App\Form\YearMonthType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return WooDecision::class;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WooDecision $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('title', TextType::class, [
                'label' => 'publication.dossier.description.title',
                'required' => true,
                /*
                 * The translation below contains the following class names:
                 * bhr-form-help__do
                 * bhr-form-help__dont
                 * my-4
                 * text-sm
                 *
                 * We mention these here so that Tailwind will pick them up.
                 */
                'help' => 'publication.dossier.description.help',
                'help_html' => true,
                'attr' => [
                    'class' => 'w-full',
                ],
                'empty_data' => '',
            ])
            ->add('date_from', YearMonthType::class, [
                'label' => 'Van',
                'row_attr' => [
                    'data-fieldset' => 'date_from date_to',
                    'data-legend' => 'Periode waarop verzoek ziet',
                    'data-required' => false,
                ],
                'required' => false,
                'placeholder' => 'kies beginmaand',
                YearMonthType::DAY_MODE => YearMonthType::MODE_FROM,
                'property_path' => 'dateFrom',
                'dossier' => $dossier,
            ])
            ->add('date_to', YearMonthType::class, [
                'label' => 'tot en met',
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
        $this->addNewDossierFields($builder);
        $this->addSubmits($builder);
    }
}
