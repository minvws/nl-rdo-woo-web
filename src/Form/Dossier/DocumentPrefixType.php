<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\DocumentPrefix;
use App\Form\Transformer\DocumentPrefixTransformer;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
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
                'label' => 'admin.dossiers.convenant.form.details.prefix',
                'choice_label' => 'prefix',
                'required' => true,
                'help' => 'admin.dossiers.convenant.form.details.prefix_help',
                'help_html' => true,
                'placeholder' => 'admin.dossiers.convenant.form.details.prefix_placeholder',
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
