<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType as DecisionTypeEnum;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormFactory;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class DecisionType extends AbstractDossierStepType
{
    public function __construct(
        private readonly DossierFormFactory $dossierFormFactory,
    ) {
    }

    public function getDataClass(): string
    {
        return WooDecision::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dossierForm = $this->dossierFormFactory->for($builder);
        $dossier = $dossierForm->getDossier();

        if ($dossier->getStatus()->isConcept()) {
            $builder->add('decision', EnumType::class, [
                'label' => 'admin.decisions.decision',
                'required' => true,
                'help' => 'admin.decisions.decision_help',
                'class' => DecisionTypeEnum::class,
                'expanded' => true,
            ]);
        }

        $builder
            ->add('summary', TextareaType::class, [
                'label' => 'admin.decisions.summary',
                'required' => true,
                'help' => 'admin.decisions.summary_help',
                'attr' => ['rows' => 5],
                'empty_data' => '',
            ]);

        $dossierForm->addDocumentField();

        $dossierForm->addSubmits();
    }
}
