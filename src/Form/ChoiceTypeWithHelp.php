<?php

declare(strict_types=1);

namespace Shared\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceTypeWithHelp extends ChoiceType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined(['choice_help_labels']);
        $resolver->setAllowedTypes('choice_help_labels', 'array');
    }

    /**
     * @param mixed[] $options
     */
    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        parent::finishView($view, $form, $options);

        foreach ($view->children as $child) {
            $child->vars['help'] = $options['choice_help_labels'][$child->vars['value']] ?? [];
        }
    }
}
