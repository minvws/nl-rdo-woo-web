<?php

declare(strict_types=1);

namespace Admin\Form\User;

use Admin\Form\Transformer\RoleTransformer;
use Override;
use Shared\Form\ChoiceTypeWithHelp;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * This form type is needed since the UserRoleFormType adds user roles dynamically (PRE_SET_DATA event), and we need a
 * transformer on it. Normally, this cannot be done directly (as the form is already build, and transformers are added
 * before the build phase. This is a workaround.
 */
class RoleType extends ChoiceTypeWithHelp
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->addModelTransformer(new RoleTransformer());
    }
}
