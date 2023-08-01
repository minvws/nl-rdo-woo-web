<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @template-extends AbstractType<DocumentPrefixType>
 *
 * @template-implements DataTransformerInterface<string,string>
 */
class DocumentPrefixType extends AbstractType implements DataTransformerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prefix', TextType::class, [
                'label' => 'Prefix',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 5, 'max' => 30]),
                    new Regex('/^[0-9a-zA-Z-]+$/', 'Prefix mag alleen bestaan uit letters, cijfers en streepjes'),
                ],
                'help' => 'Prefix',
            ])
            ->add('description', TextType::class, [
                'label' => 'Omschrijving',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'help' => 'Beknopte omschrijving voor deze prefix',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }

    public function transform(mixed $value): string
    {
        if (! is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        return strtoupper($value);
    }

    public function reverseTransform(mixed $value): string
    {
        return strval($value);
    }
}