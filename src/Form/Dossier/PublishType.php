<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Dossier;
use App\Service\DossierWorkflow\DossierWorkflow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<PublishType>
 */
class PublishType extends AbstractType
{
    public function __construct(
        private readonly DossierWorkflow $workflow,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();
        $workflowStatus = $this->workflow->getStatus($dossier);

        if (in_array(Dossier::STATUS_PREVIEW, $workflowStatus->getAllowedStatusUpdates())) {
            $builder
                ->add('publish_preview', SubmitType::class, [
                    'label' => 'Publiceren als preview',
                ]);
        }

        if (in_array(Dossier::STATUS_PUBLISHED, $workflowStatus->getAllowedStatusUpdates())) {
            $builder
                ->add('publish', SubmitType::class, [
                    'label' => 'Publiceren als openbaar',
                ]);
        }
    }
}
