<?php

declare(strict_types=1);

namespace Shared\Form\Inquiry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<AdministrationActionsType>
 */
class AdministrationActionsType extends AbstractType
{
    public const ACTION_REGENERATE_INVENTORY = 'regenerate_inventory';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'label' => 'admin.administration_actions.action',
                'choices' => [
                    'admin.administration_actions.regenerate_inventory' => self::ACTION_REGENERATE_INVENTORY,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.administration_actions.submit',
            ]);
    }
}
