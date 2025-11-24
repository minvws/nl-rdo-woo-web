<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\OtherPublication;

use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\FormBuilderInterface;

class ContentFormType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return OtherPublication::class;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSummaryField($builder);
        $this->addDocumentField($builder);
        $this->addSubmits($builder);
    }
}
