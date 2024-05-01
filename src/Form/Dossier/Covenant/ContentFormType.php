<?php

declare(strict_types=1);

namespace App\Form\Dossier\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Form\Dossier\AbstractDossierStepType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentFormType extends AbstractDossierStepType
{
    public function getDataClass(): string
    {
        return Covenant::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Covenant $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('parties', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'required' => true,
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'admin.dossiers.convenant.form.content.summary_label',
                'required' => true,
                'attr' => ['rows' => 5],
                'empty_data' => '',
            ])
            ->add('document', DocumentType::class)
            ->add('previous_version_link', TextType::class, [
                'label' => 'admin.dossiers.convenant.form.content.previous_version_link_label',
                'help' => 'admin.dossiers.convenant.form.content.previous_version_link_description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full',
                ],
                'empty_data' => '',
                'property_path' => 'previousVersionLink',
            ]);

        $this->addSubmits($dossier, $builder);
    }

    public function addSubmits(Covenant $dossier, FormBuilderInterface $builder): void
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
