<?php

declare(strict_types=1);

namespace App\Form\Inquiry;

use App\Entity\Inquiry;
use App\Repository\InquiryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @template-extends AbstractType<InquiryFilterFormType>
 */
class InquiryFilterFormType extends AbstractType
{
    public const CASE = 'case';

    public function __construct(
        private readonly InquiryRepository $repository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Inquiry $inquiry */
        $inquiry = $builder->getData();

        $dossierCount = $inquiry->getDossiers()->count();
        $dossiersName = $this->translator->trans('these # dossiers', ['count' => $dossierCount]);
        $submitLabel = $this->translator->trans('Filter');

        $choices = [
            $this->translator->trans(
                '# documents with casenumber {casenumber} in {dossiers}',
                [
                    'count' => $this->repository->countDocumentsForPubliclyAvailableDossiers($inquiry),
                    '{dossiers}' => $dossiersName,
                    '{casenumber}' => $inquiry->getCasenr(),
                ]
            ) => self::CASE,
        ];

        foreach ($this->repository->getDocCountsByDossier($inquiry) as $row) {
            $label = $this->translator->trans(
                '# documents with casenumber {casenumber} in dossier {dossierTitle}',
                [
                    'count' => $row['doccount'],
                    '{casenumber}' => $inquiry->getCasenr(),
                    '{dossierTitle}' => $row['title'],
                ]
            );
            $choices[$label] = $row['dossierNr'];
        }

        // Add 'autofocus' attribute to the choice for the active filter, but only if it was actively chosen (not using the default)
        $choiceAttributes = [];
        if (! empty($options['filterParam'])) {
            foreach ($choices as $key => $choice) {
                if ($choice === $options['filterParam']) {
                    $choiceAttributes[$key] = ['autofocus' => true];
                    break;
                }
            }
        }

        $builder
            ->add('filter', ChoiceType::class, [
                'label' => $submitLabel,
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'choices' => $choices,
                'mapped' => false,
                'data' => self::CASE,
                'label_attr' => [
                    'class' => 'sr-only',
                ],
                'label_html' => true,
                'choice_attr' => $choiceAttributes,
            ])
            ->add('submit', SubmitType::class, [
                'label' => $submitLabel,
                'attr' => [
                    'class' => 'js:hidden',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'filterParam' => '',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
