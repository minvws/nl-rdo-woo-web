<?php

declare(strict_types=1);

namespace App\Form\Inquiry;

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
    public const ACTION_REGENERATE_ARCHIVES = 'regenerate_archives';

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'label' => 'Inquiry action',
                'choices' => [
                    'Regenerate inventory' => self::ACTION_REGENERATE_INVENTORY,
                    'Regenerate archives' => self::ACTION_REGENERATE_ARCHIVES,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Execute action (async)',
            ]);
    }
}
