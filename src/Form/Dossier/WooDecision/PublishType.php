<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Shared\Form\PlainDateType;
use Shared\Validator\PlainDate\PlainDateAfterOrEqual;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<PublishType>
 */
class PublishType extends AbstractType
{
    use DossierFormBuilderTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WooDecision $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isConceptOrScheduled()) {
            $builder->add('preview_date', PlainDateType::class, [
                'label' => 'admin.decision.preview_date',
                'help' => 'admin.decision.preview_date_help',
                'required' => true,
                'widget' => 'single_text',
                'data' => $dossier->getPreviewDate() ?? PlainDate::today(),
                'constraints' => [
                    new NotBlank(),
                    new PlainDateAfterOrEqual(
                        date: 'today',
                        message: 'preview_date_must_be_today_or_future',
                    ),
                ],
            ]);
            $builder->add('publication_date', PlainDateType::class, [
                'label' => 'admin.decision.publication_date',
                'help' => 'admin.decision.publication_date_help',
                'required' => true,
                'widget' => 'single_text',
                'data' => $dossier->getPublicationDate() ?? PlainDate::today(),
                'constraints' => [
                    new NotBlank(),
                    new PlainDateAfterOrEqual(
                        message: 'publication_date_must_be_today_or_past_preview_date',
                        propertyPath: 'parent.all[preview_date].data',
                    ),
                ],
            ]);
        } else {
            $builder->add('publication_date', PlainDateType::class, [
                'label' => 'Datum openbare publicatie',
                'required' => true,
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(),
                    new PlainDateAfterOrEqual(
                        date: 'today',
                        message: 'publication_date_must_be_today_or_future',
                    ),
                ],
            ]);
        }

        $this->addSaveAndPublishSubmit($builder);
    }
}
