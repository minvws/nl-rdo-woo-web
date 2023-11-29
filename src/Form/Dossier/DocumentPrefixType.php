<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\DocumentPrefix;
use App\Entity\User;
use App\Form\Transformer\DocumentPrefixTransformer;
use App\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<DocumentPrefixType>
 */
class DocumentPrefixType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly Security $security
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DocumentPrefixTransformer($this->doctrine));
        $this->addDocumentPrefixField($builder);
    }

    /**
     * Departments are special as they depend on the current logged in user and also how many
     * departments are found.
     */
    private function addDocumentPrefixField(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var User|UserInterface|null $user */
            $user = $this->security->getUser();
            if (! $user) {
                throw new \RuntimeException('User is not logged in');
            }

            /** @var User $user */
            if ($user->hasRole(Roles::ROLE_SUPER_ADMIN)) {
                // Super admin can see all the document prefixes
                $prefixes = [];
            } else {
                $prefixes = $user->getOrganisation()->getDocumentPrefixes()->toArray();
            }

            $options = [
                'class' => DocumentPrefix::class,
                'label' => 'Prefix voor documenten',
                'choice_label' => 'prefix_and_description',
                'required' => true,
                'help' => 'Deze voegen we automatisch toe aan de bestandsnaam van documenten. '
                    . 'Let op: dit is na opslaan van de basisgegevens niet meer aan te passen.',
                'placeholder' => 'Kies een prefix',
                'constraints' => [
                    new NotBlank(),
                ],
            ];

            $form = $event->getForm();

            // No entities given means we can select all entities (ie: user admin)
            if (count($prefixes) == 0) {
                $options['query_builder'] = function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')->select('d')->orderBy('d.prefix', 'ASC');
                };

                $form->add('documentPrefix', EntityType::class, $options);

                return;
            }

            // If we have more than one entity, we need to use a choice type
            if (count($prefixes) > 1) {
                $options['choices'] = $prefixes;
                $form->add('documentPrefix', EntityType::class, $options);

                return;
            }

            // One entity does not give us a choice, so we remove the placeholder
            unset($options['placeholder']);
            $options['choices'] = $prefixes;
            $form->add('documentPrefix', EntityType::class, $options);
        });
    }
}
