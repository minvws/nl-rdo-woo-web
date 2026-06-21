<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilderInterface;

readonly class DossierFormFactory
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function for(FormBuilderInterface $builder): DossierForm
    {
        return new DossierForm($builder, $this->security);
    }
}
