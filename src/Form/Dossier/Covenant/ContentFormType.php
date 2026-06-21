<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\Covenant;

use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormFactory;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentFormType extends AbstractDossierStepType
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
        $builder
            ->add('parties', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'required' => true,
            ]);

        $dossierForm = $this->dossierFormFactory->for($builder);
        $dossierForm->addSummaryField();
        $dossierForm->addDocumentField();

        $builder
            ->add('previous_version_link', TextType::class, [
                'label' => 'admin.dossiers.covenant.form.content.previous_version_link_label',
                'help' => 'admin.dossiers.covenant.form.content.previous_version_link_description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full',
                ],
                'empty_data' => '',
                'property_path' => 'previousVersionLink',
            ]);

        $dossierForm->addSubmits();
    }
}
