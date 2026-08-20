<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\DraftDecision;

use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormFactory;
use Symfony\Component\Form\FormBuilderInterface;

class ContentFormType extends AbstractDossierStepType
{
    public function __construct(
        private readonly DossierFormFactory $dossierFormFactory,
    ) {
    }

    public function getDataClass(): string
    {
        return DraftDecision::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dossierForm = $this->dossierFormFactory->for($builder);
        $dossierForm->addSummaryField();
        $dossierForm->addDocumentField();
        $builder->add('attachment', AttachmentFieldType::class);
        $dossierForm->addSubmits();
    }
}
