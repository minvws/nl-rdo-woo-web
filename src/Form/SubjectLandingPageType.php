<?php

declare(strict_types=1);

namespace Shared\Form;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Form\Transformer\StringToLandingPageSlugTransformer;
use Shared\Form\Transformer\StringToLandingPageTitleTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Webmozart\Assert\Assert;

/**
 * @template-extends AbstractType<SubjectLandingPageType>
 */
class SubjectLandingPageType extends AbstractType
{
    private readonly string $publicBaseUrl;

    public function __construct(string $publicBaseUrl)
    {
        $this->publicBaseUrl = $publicBaseUrl;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Subject $subject */
        $subject = $builder->getData();
        Assert::isInstanceOf($subject, Subject::class);

        $builder
            ->add('landing_page_status', EnumType::class, [
                'label' => 'admin.subject.landing_page.status',
                'help' => 'admin.subject.landing_page.status_help',
                'class' => SubjectLandingPageStatus::class,
                'choice_label' => static fn (SubjectLandingPageStatus $status): string => 'admin.subject.landing_page.status.' . $status->value,
                'required' => true,
                'property_path' => 'landingPageStatus',
                'data' => $subject->getLandingPageStatus() ?? SubjectLandingPageStatus::CONCEPT,
            ])
            ->add('landing_page_slug', TextType::class, [
                'label' => 'admin.subject.landing_page.slug',
                'required' => true,
                'help' => 'admin.subject.landing_page.slug_help',
                'help_translation_parameters' => [
                    'publicBaseUrl' => $this->publicBaseUrl,
                ],
                'empty_data' => '',
                'property_path' => 'landingPageSlug',
            ])
            ->add('landing_page_title', TextType::class, [
                'label' => 'admin.subject.landing_page.title',
                'required' => true,
                'help' => 'admin.subject.landing_page.title_help',
                'empty_data' => '',
                'property_path' => 'landingPageTitle',
            ])
            ->add('landing_page_description', TextareaType::class, [
                'label' => 'admin.subject.landing_page.description',
                'required' => true,
                'empty_data' => '',
                'property_path' => 'landingPageDescription',
                'constraints' => [
                    new NotBlank(message: 'subject_landing_page_description_required'),
                ],
                'attr' => [
                    'data-is-markdown' => 'true',
                ],
            ])
            ->add('has_visible_landing_page_content_tree', CheckboxType::class, [
                'label' => 'admin.subject.landing_page.visible_content_tree',
                'required' => false,
                'property_path' => 'hasVisibleLandingPageContentTree',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'global.save',
            ]);

        $builder->get('landing_page_slug')->addModelTransformer(new StringToLandingPageSlugTransformer());
        $builder->get('landing_page_title')->addModelTransformer(new StringToLandingPageTitleTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subject::class,
        ]);
    }
}
