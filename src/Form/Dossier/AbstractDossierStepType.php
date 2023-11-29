<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<AbstractDossierStepType>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractDossierStepType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
        ]);

        $resolver->setDefaults([
            'validation_groups' => function (FormInterface $form): array {
                if ($form->has('cancel')) {
                    /** @var SubmitButton $cancelSubmit */
                    $cancelSubmit = $form->get('cancel');
                    if ($cancelSubmit->isClicked()) {
                        return [];
                    }
                }

                return ['Default'];
            },
        ]);
    }
}
