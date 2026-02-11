<?php

declare(strict_types=1);

namespace Admin\Form\User;

use Override;
use Shared\Form\ChoiceTypeWithHelp;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

use function array_map;
use function is_null;
use function str_replace;
use function strtolower;
use function strtoupper;

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

        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if (is_null($data)) {
                    return [];
                }

                return array_map(fn ($role) => strtolower(str_replace('ROLE_', '', $role)), $data);
            },
            fn ($roles) => array_map(fn ($role) => 'ROLE_' . strtoupper((string) $role), $roles)
        ));
    }
}
