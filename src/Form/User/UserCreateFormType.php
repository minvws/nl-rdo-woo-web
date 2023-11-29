<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\Organisation;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Roles;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @template-extends AbstractType<UserCreateFormType>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserCreateFormType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $repository,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'First and last name',
                'label_attr' => [
                    'class' => 'bhr-label text-xl mb-0',
                ],
                'help' => 'For example John Doe',
                'empty_data' => '',
                'constraints' => [
                    new Length(['min' => 1, 'max' => 255]),
                ],
            ])
            ->add('organisation', EntityType::class, [
                'label' => 'Organisatie',
                'label_attr' => [
                    'class' => 'bhr-label text-xl mb-2',
                ],
                'class' => Organisation::class,
                'choice_label' => function (Organisation $organisation) {
                    return $organisation->getName() . ' (' . $organisation->getDepartment()->getShortTag() . ')';
                },
                'placeholder' => 'Selecteer een organisatie',
                'required' => true,
                'constraints' => [
                    new Required(),
                ],
            ])
            ->add('roles', HiddenType::class)
            ->add('email', EmailType::class, [
                'label' => 'E-mail address', // @codingStandardsIgnoreStart
                'help' => 'Users identify themselves with their email address. We do not use the email address to share password or other login information.', // @codingStandardsIgnoreEnd
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                    new Length(['min' => 4, 'max' => 180]),
                    new Callback([$this, 'validateEmail']),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Create user',
            ])
        ;

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
                'label' => 'Access roles',
                'label_attr' => [
                    'class' => 'bhr-label text-xl mb-0',
                ],
                'help' => 'Indicate what this user is allowed to do, multiple options are possible',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select at least one role.',
                    ]),
                ],
            ]);
        });
    }

    public function validateEmail(string $input, ExecutionContextInterface $context): void
    {
        if ($this->repository->findOneBy(['email' => $input]) !== null) {
            $context->buildViolation('This email address is already in use')
                ->atPath('email')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
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
