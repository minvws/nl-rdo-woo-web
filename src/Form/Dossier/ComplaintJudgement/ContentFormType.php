<?php

declare(strict_types=1);

namespace App\Form\Dossier\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DocumentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentFormType extends AbstractDossierStepType
{
    public function getDataClass(): string
    {
        return ComplaintJudgement::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ComplaintJudgement $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('summary', TextareaType::class, [
                'label' => 'admin.dossiers.complaint-judgement.summary',
                'required' => true,
                'attr' => ['rows' => 5],
                'empty_data' => '',
            ])
            ->add('document', DocumentType::class);

        $this->addSubmits($dossier, $builder);
    }

    public function addSubmits(ComplaintJudgement $dossier, FormBuilderInterface $builder): void
    {
        if ($dossier->getStatus()->isConcept()) {
            $builder
                ->add('next', SubmitType::class, [
                    'label' => 'global.save_and_continue',
                    'attr' => [
                        'data-first-button' => true,
                    ],
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'global.save_draft',
                    'attr' => [
                        'class' => 'bhr-button--secondary',
                        'data-last-button' => true,
                    ],
                ]);
        } else {
            $builder
                ->add('save', SubmitType::class, [
                    'label' => 'global.save_edit',
                    'attr' => [
                        'data-first-button' => true,
                    ],
                ])
                ->add('cancel', SubmitType::class, [
                    'label' => 'global.cancel',
                    'attr' => [
                        'class' => 'bhr-button--secondary',
                        'data-last-button' => true,
                    ],
                    'validate' => false,
                ]);
        }
    }
}
