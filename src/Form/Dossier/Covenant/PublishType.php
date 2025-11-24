<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\Covenant;

use Shared\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<PublishType>
 */
class PublishType extends AbstractType
{
    use DossierFormBuilderTrait;

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addPublicationDateField($builder);
        $this->addSaveAndPublishSubmit($builder);
    }
}
