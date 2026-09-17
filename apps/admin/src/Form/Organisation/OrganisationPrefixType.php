<?php

declare(strict_types=1);

namespace Admin\Form\Organisation;

use Admin\Form\Transformer\StringToOrganisationPrefixTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class OrganisationPrefixType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new StringToOrganisationPrefixTransformer());
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
