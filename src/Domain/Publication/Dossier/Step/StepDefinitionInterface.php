<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Step;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface StepDefinitionInterface
{
    public function getName(): StepName;

    public function getDossierType(): DossierType;

    public function isCompleted(AbstractDossier $dossier, ValidatorInterface $validator): bool;

    public function getConceptEditRouteName(): string;

    public function getEditRouteName(): string;
}
