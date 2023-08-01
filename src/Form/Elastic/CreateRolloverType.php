<?php

declare(strict_types=1);

namespace App\Form\Elastic;

use App\Model\CreateElasticsearchRollover;
use App\Service\Elastic\MappingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<CreateRolloverType>
 */
class CreateRolloverType extends AbstractType
{
    public function __construct(protected MappingService $mappingService)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mappingVersion', IntegerType::class, [
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                /** @var CreateElasticsearchRollover $data */
                $data = $event->getData();

                $version = $data->getMappingVersion();
                $isValid = $this->mappingService->isValidMappingVersion($version);
                if (! $isValid) {
                    $form = $event->getForm();
                    /** @var \Symfony\Component\Form\FormInterface $mappingField */
                    $mappingField = $form['mappingVersion'];
                    $mappingField->addError(new FormError('Mapping version does not exist'));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateElasticsearchRollover::class,
        ]);
    }
}
