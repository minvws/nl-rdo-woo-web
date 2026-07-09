<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This field is only used to get entity validation errors for the 'noticeNotPublic' property, so the property_path is set to map errors.
 * The notice entity is updated using a Vue component with API calls, so it is not mapped.
 *
 * Only the form_errors should be rendered for this field, the field itself should not be used.
 */
class NoticeNotPublicType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'error_bubbling' => false,
            'compound' => false,
            'mapped' => false,
            'property_path' => 'noticeNotPublic',
        ]);
    }
}
