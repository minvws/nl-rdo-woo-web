<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Webmozart\Assert\Assert;

/**
 * @template-extends AbstractType<AbstractDossierStepType>
 */
abstract class AbstractDossierStepType extends AbstractType
{
    abstract public function getDataClass(): string;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->getDataClass(),
        ]);

        $resolver->setDefaults([
            'validation_groups' => static function (FormInterface $form): array {
                if ($form->has('cancel')) {
                    $cancelSubmit = $form->get('cancel');
                    Assert::isInstanceOf($cancelSubmit, SubmitButton::class);

                    if ($cancelSubmit->isClicked()) {
                        return [];
                    }
                }

                return [Constraint::DEFAULT_GROUP];
            },
        ]);
    }
}
