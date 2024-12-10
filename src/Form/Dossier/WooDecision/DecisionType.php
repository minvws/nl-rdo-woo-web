<?php

declare(strict_types=1);

namespace App\Form\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType as DecisionTypeEnum;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class DecisionType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return WooDecision::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WooDecision $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isConcept()) {
            $builder->add('decision', EnumType::class, [
                'label' => 'Besluit',
                'required' => true,
                'help' => 'Hoe komen we tegemoet aan het verzoek?',
                'class' => DecisionTypeEnum::class,
                'expanded' => true,
            ]);
        }

        $builder
            ->add('summary', TextareaType::class, [
                'label' => 'Samenvatting van het besluit',
                'required' => true,
                'help' => 'Vat samen waarom het bovenstaande besluit is genomen',
                'attr' => ['rows' => 5],
                'empty_data' => '',
            ]);

        $this->addDocumentField($builder);

        $builder
            ->add('decision_date', DateType::class, [
                'required' => true,
                'label' => 'Datum waarop het besluit genomen is',
                'help' => 'Vul de formele besluit-datum in',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $dossier->getDecisionDate() ?? new \DateTimeImmutable(),
                'property_path' => 'decisionDate',
            ]);

        $this->addSubmits($builder);
    }
}
