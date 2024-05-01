<?php

declare(strict_types=1);

namespace App\Form\Dossier\Covenant;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This field is only used to get entity validation errors for the 'document' property, so the property_path is set to map errors.
 * The document entity is updated using a Vue component with API calls, so it is not mapped.
 *
 * Only the form_errors should be rendered for this field, the field itself should not be used.
 */
class DocumentType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'error_bubbling' => false,
            'compound' => false,
            'mapped' => false,
            'property_path' => 'document',
        ]);
    }
}
