<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

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
        /** @var WooDecision $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isConceptOrScheduled()) {
            $builder->add('preview_date', DateType::class, [
                'label' => 'admin.decision.preview_date',
                'help' => 'admin.decision.preview_date_help',
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $dossier->getPreviewDate() ?? new \DateTimeImmutable(),
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual(
                        new \DateTimeImmutable('today midnight'),
                        message: 'preview_date_must_be_today_or_future'
                    ),
                ],
            ]);
            $builder->add('publication_date', DateType::class, [
                'label' => 'admin.decision.publication_date',
                'help' => 'admin.decision.publication_date_help',
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $dossier->getPublicationDate() ?? new \DateTimeImmutable(),
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[preview_date].data',
                        'message' => 'publication_date_must_be_today_or_past_preview_date',
                    ]),
                ],
            ]);
        } else {
            $builder->add('publication_date', DateType::class, [
                'label' => 'Datum openbare publicatie',
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual(
                        new \DateTimeImmutable('today midnight'),
                        message: 'publication_date_must_be_today_or_future'
                    ),
                ],
            ]);
        }

        $this->addSaveAndPublishSubmit($builder);
    }
}
