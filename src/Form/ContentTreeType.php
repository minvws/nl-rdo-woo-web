<?php

declare(strict_types=1);

namespace Shared\Form;

use Override;
use Shared\Form\Transformer\ContentTreeToJsonTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentTreeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ContentTreeToJsonTransformer());
    }

    #[Override]
    public function getParent(): string
    {
        return TextareaType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'content_tree';
    }
}
