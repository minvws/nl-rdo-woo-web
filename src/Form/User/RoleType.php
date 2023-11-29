<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\ChoiceTypeWithHelp;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * This form type is needed since the UserRoleFormType adds user roles dynamically (PRE_SET_DATA event), and we need a
 * transformer on it. Normally, this cannot be done directly (as the form is already build, and transformers are added
 * before the build phase. This is a workaround.
 */
class RoleType extends ChoiceTypeWithHelp
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if (is_null($data)) {
                    return [];
                }

                return array_map(function ($role) {
                    return strtolower(str_replace('ROLE_', '', $role));
                }, $data);
            },
            function ($roles) {
                return array_map(function ($role) {
                    return 'ROLE_' . strtoupper($role);
                }, $roles);
            }
        ));
    }
}
