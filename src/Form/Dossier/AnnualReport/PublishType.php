<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\AnnualReport;

use Shared\Form\Dossier\DossierFormFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<PublishType>
 */
class PublishType extends AbstractType
{
    public function __construct(
        private readonly DossierFormFactory $dossierFormFactory,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dossierForm = $this->dossierFormFactory->for($builder);
        $dossierForm->addPublicationDateField();
        $dossierForm->addSaveAndPublishSubmit();
    }
}
