<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<StateChangeFormType>
 */
class StateChangeFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // All elements are added through the PRE_SET_DATA event. This is because we need to have
        // the dossier object to determine the allowed state changes.
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    public function onPreSetData(PreSetDataEvent $event): void
    {
        $form = $event->getForm();

        /** @var Dossier $dossier */
        $dossier = $event->getData();
        if (! $dossier instanceof Dossier) {
            return;
        }

        $form->add('state', ChoiceType::class, [
                'label' => 'State',
                'mapped' => false,
                'data' => $dossier->getStatus(),
                'choices' => [
                    'Concept' => Dossier::STATUS_CONCEPT,
                    'Gereed voor publicatie' => Dossier::STATUS_COMPLETED,
                    'In preview' => Dossier::STATUS_PREVIEW,
                    'Gepubliceerd' => Dossier::STATUS_PUBLISHED,
                    'Ongepubliceerd' => Dossier::STATUS_RETRACTED,
                ],
                'choice_attr' => function ($choice, $key, $value) use ($dossier) {
                    unset($key);
                    unset($choice);
                    if ($dossier->getStatus() == $value) {
                        return [];
                    }

                    return $dossier->isAllowedState($value) ? [] : ['disabled' => 'disabled'];
                },
            ])
        ;

        $form->add('submit', SubmitType::class, [
            'label' => 'Update dossier state',
            'attr' => [
                'class' => 'btn btn-success',
            ],
        ]);
    }
}
