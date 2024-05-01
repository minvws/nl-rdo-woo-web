<?php

declare(strict_types=1);

namespace App\Form\Dossier\WooDecision;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<PublishType>
 */
class PublishType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isConceptOrScheduled()) {
            $builder->add('preview_date', DateType::class, [
                'label' => 'Datum feitelijke verstrekking',
                'help' => 'Kies de datum waarop het besluit aan de verzoeker is/wordt verstrekt. '
                        . 'Als er zaaknummers aan documenten in dit besluit zijn gekoppeld, kan de verzoeker deze vanaf dat moment online inzien.',
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $dossier->getPreviewDate() ?? new \DateTimeImmutable(),
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual(
                        new \DateTimeImmutable('today midnight'),
                        message: 'De datum van feitelijke verstrekking moet gelijk of later zijn dan vandaag'
                    ),
                ],
            ]);
            $builder->add('publication_date', DateType::class, [
                'label' => 'Datum openbare publicatie',
                'help' => 'Kies de datum waarop het besluit voor iedereen publiek toegankelijk wordt.',
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $dossier->getPublicationDate() ?? new \DateTimeImmutable(),
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[preview_date].data',
                        'message' => 'De datum van openbare publicatie moet gelijk of later zijn dan de datum van feitelijke verstrekking',
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
                        message: 'De datum van feitelijke verstrekking moet gelijk of later zijn dan vandaag'
                    ),
                ],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Opslaan en klaarzetten',
        ]);
    }
}
