<?php

declare(strict_types=1);

namespace Shared\Form;

use Override;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function is_string;

class PlainDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function (?PlainDate $date): string {
                return $date ? $date->toString() : '';
            },
            function (?string $value): ?PlainDate {
                return is_string($value) && $value !== '' ? PlainDate::create($value) : null;
            },
        ));
    }

    #[Override]
    public function getParent(): string
    {
        return DateType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'input' => 'string',
            'widget' => 'single_text',
        ]);
    }
}
