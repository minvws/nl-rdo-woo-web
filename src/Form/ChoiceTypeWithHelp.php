<?php

declare(strict_types=1);

namespace Shared\Form;

use Override;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;
use function is_array;
use function is_string;

class ChoiceTypeWithHelp extends ChoiceType
{
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined(['choice_help_labels']);
        $resolver->setAllowedTypes('choice_help_labels', 'array');
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        parent::finishView($view, $form, $options);

        foreach ($view->children as $child) {
            $child->vars['help'] = $this->generateChildHelp($child, $options);
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return string|array<array-key, mixed>
     */
    private function generateChildHelp(FormView $formView, array $options): string|array
    {
        $value = $formView->vars['value'];

        if (
            is_string($value)
            && array_key_exists('choice_help_labels', $options)
            && is_array($options['choice_help_labels'])
            && array_key_exists($value, $options['choice_help_labels'])
            && is_string($options['choice_help_labels'][$value])
        ) {
            return $options['choice_help_labels'][$value];
        }

        return [];
    }
}
