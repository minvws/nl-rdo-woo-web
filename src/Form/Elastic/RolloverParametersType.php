<?php

declare(strict_types=1);

namespace Shared\Form\Elastic;

use Shared\Domain\Search\Index\Rollover\MappingService;
use Shared\Domain\Search\Index\Rollover\RolloverParameters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @template-extends AbstractType<RolloverParametersType>
 */
class RolloverParametersType extends AbstractType
{
    public function __construct(
        protected MappingService $mappingService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mappingVersion', IntegerType::class, [
                'label' => 'admin.elastic.mapping_version',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'global.save',
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                /** @var RolloverParameters $data */
                $data = $event->getData();

                $version = $data->getMappingVersion();
                $isValid = $this->mappingService->isValidMappingVersion($version);
                if (! $isValid) {
                    $form = $event->getForm();
                    /** @var FormInterface $mappingField */
                    $mappingField = $form['mappingVersion'];
                    $mappingField->addError(new FormError($this->translator->trans('Mapping version does not exist', domain: 'validators')));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RolloverParameters::class,
        ]);
    }
}
