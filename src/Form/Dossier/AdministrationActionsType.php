<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<AdministrationActionsType>
 */
class AdministrationActionsType extends AbstractType
{
    public const ACTION_REGENERATE_CLEAN_INVENTORY = 'regenerate_clean_inventory';
    public const ACTION_REGENERATE_ARCHIVES = 'regenerate_archives';
    public const ACTION_INGEST = 'ingest';
    public const ACTION_UPDATE = 'update';

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'label' => 'Dossier action',
                'choices' => [
                    'Regenerate clean inventory' => self::ACTION_REGENERATE_CLEAN_INVENTORY,
                    'Regenerate archives' => self::ACTION_REGENERATE_ARCHIVES,
                    'Re-ingest into ElasticSearch (with docs)' => self::ACTION_INGEST,
                    'Re-index dossier into ElasticSearch (no document ingest)' => self::ACTION_UPDATE,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Execute action (async)',
            ]);
    }
}
