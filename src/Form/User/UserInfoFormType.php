<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User;
use App\Roles;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<UserInfoFormType>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserInfoFormType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * @param array{data: User} $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full name',
                'empty_data' => '',
                'attr' => [
                    'help' => 'The name of this user',
                ],
                'help' => 'For example John Doe',
                'constraints' => [
                    new Length(['min' => 1, 'max' => 255]),
                ],
            ])
            ->add('organisation', TextType::class, [
                'disabled' => true,
                'label' => 'Organisatie',
                'data' => $options['data']->getOrganisation()->getName() .
                    ' (' . $options['data']->getOrganisation()->getDepartment()->getShortTag() . ')',
            ])
            ->add('email', EmailType::class, [
                'disabled' => true,
                'label' => 'E-mail address', // @codingStandardsIgnoreStart
                'help' => 'Users identify themselves with their email address. We do not use the email address to provide passwords or other login information.', // @codingStandardsIgnoreEnd
                'help_attr' => [
                    'class' => 'bhr-form-help text-sm pb-2',
                ],
                'attr' => [
                    'class' => 'bhr-value',
                ],
            ])
            ->add('roles', HiddenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ]);

        $builder->addeventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            // It's possible that a user has multiple roles. We add all roles that are allowed to be assigned.
            $allowedRoles = [];
            /** @var User $user */
            $user = $this->security->getUser();
            foreach ($user->getRoles() as $role) {
                $allowedRoles = array_merge($allowedRoles, Roles::getRoleHierarchy($role));
            }

            $roleDetails = Roles::roleDetails();
            $choices = $this->createChoices($roleDetails, $allowedRoles);

            $form->add('roles', RoleType::class, [
                'choices' => $choices,
                'choice_help_labels' => $this->createHelp(Roles::roleDetails()),
                'multiple' => true,
                'expanded' => true,
                'label' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select at least one role.',
                    ]),
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }

    /**
     * @param array{role: string, description: string, help: string}[] $roleDetails
     * @param array<string>                                            $allowedRoles
     *
     * @return array<string, string>
     */
    protected function createChoices(array $roleDetails, array $allowedRoles): array
    {
        $ret = [];
        foreach ($roleDetails as $detail) {
            if (! in_array($detail['role'], $allowedRoles)) {
                continue;
            }

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
