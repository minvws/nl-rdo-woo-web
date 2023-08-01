<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Form\ChoiceTypeWithHelp;
use App\Roles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<UserRoleFormType>
 */
class UserRoleFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', ChoiceTypeWithHelp::class, [
                'choices' => $this->createChoices(Roles::roleDetails()),
                'choice_help_labels' => $this->createHelp(Roles::roleDetails()),
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;

        // Transform ROLE_ADMIN to 'admin' and back so we have a nice form.
        $builder->get('roles')->addModelTransformer(new CallbackTransformer(
            function ($data) {
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }

    /**
     * @param array{role: string, description: string, help: string}[] $roleDetails
     *
     * @return array<string, string>
     */
    protected function createChoices(array $roleDetails): array
    {
        $ret = [];
        foreach ($roleDetails as $detail) {
            $key = strtolower(str_replace('ROLE_', '', $detail['role']));
            $ret[$detail['description']] = $key;
        }

        return $ret;
    }

    /**
     * @param array{role: string, description: string, help: string}[] $roleDetails
     *
     * @return array<string, string>
     */
    protected function createHelp(array $roleDetails): array
    {
        $ret = [];
        foreach ($roleDetails as $detail) {
            $key = strtolower(str_replace('ROLE_', '', $detail['role']));
            $ret[$key] = $detail['help'];
        }

        return $ret;
    }
}
