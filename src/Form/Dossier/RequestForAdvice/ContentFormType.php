<?php

declare(strict_types=1);

namespace App\Form\Dossier\RequestForAdvice;

use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentFormType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return RequestForAdvice::class;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSummaryField($builder);
        $this->addDocumentField($builder);

        $builder
            ->add('link', TextType::class, [
                'label' => 'admin.dossiers.request-for-advice.form.content.link_label',
                'help' => 'admin.dossiers.request-for-advice.form.content.link_description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full',
                ],
                'empty_data' => '',
            ]);

        $this->addSubmits($builder);
    }
}
