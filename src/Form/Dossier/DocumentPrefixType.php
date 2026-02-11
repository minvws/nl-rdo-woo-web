<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Form\Transformer\DocumentPrefixTransformer;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use function count;

/**
 * @template-extends AbstractType<DocumentPrefixType>
 */
class DocumentPrefixType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new DocumentPrefixTransformer($this->doctrine));
        $this->addDocumentPrefixField($builder);
    }

    /**
     * Departments are special as they depend on the current logged-in user and also how many
     * departments are found.
     */
    private function addDocumentPrefixField(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $prefixes = $this->authorizationMatrix->getActiveOrganisation()->getDocumentPrefixes();

            $options = [
                'class' => DocumentPrefix::class,
                'label' => 'admin.dossiers.covenant.form.details.prefix',
                'choice_label' => 'prefix',
                'required' => true,
                'help' => 'admin.dossiers.covenant.form.details.prefix_help',
                'help_html' => true,
                'placeholder' => 'admin.global.dossiers.prefix_placeholder',
                'choices' => $prefixes,
            ];

            // If we have just one prefix preselect it
            if (count($prefixes) === 1) {
                /** @var DocumentPrefix $prefix */
                $prefix = $prefixes->first();
                $options['data'] = $this->doctrine->getReference(DocumentPrefix::class, $prefix->getId());
            }

            $form->add('documentPrefix', EntityType::class, $options);
        });
    }
}
