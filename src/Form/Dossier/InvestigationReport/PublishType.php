<?php

declare(strict_types=1);

namespace App\Form\Dossier\InvestigationReport;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
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
        /** @var InvestigationReport $dossier */
        $dossier = $builder->getData();

        $builder->add('publication_date', DateType::class, [
            'label' => 'admin.dossiers.investigation-report.form.publication.publication_date_label',
            'help' => 'admin.dossiers.investigation-report.form.publication.publication_date_help',
            'required' => true,
            'widget' => 'single_text',
            'input' => 'datetime_immutable',
            'data' => $dossier->getPublicationDate() ?? new \DateTimeImmutable(),
            'constraints' => [
                new NotBlank(),
                new GreaterThanOrEqual(
                    new \DateTimeImmutable('today midnight'),
                    message: 'publication_date_must_be_today_or_future'
                ),
            ],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'global.save_and_publish',
        ]);
    }
}
