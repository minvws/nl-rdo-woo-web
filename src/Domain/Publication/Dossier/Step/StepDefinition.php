<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Step;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class StepDefinition implements StepDefinitionInterface
{
    final public function __construct(
        private StepName $name,
        private DossierType $dossierType,
    ) {
    }

    public function getName(): StepName
    {
        return $this->name;
    }

    public function getDossierType(): DossierType
    {
        return $this->dossierType;
    }

    public function isCompleted(AbstractDossier $dossier, ValidatorInterface $validator): bool
    {
        if ($dossier->getType() !== $this->dossierType) {
            throw StepException::forIncompatibleDossierInstance($this, $dossier);
        }

        $errors = $validator->validate(
            $dossier,
            null,
            DossierValidationGroup::getValidationGroupsForStep($this),
        );

        return count($errors) === 0;
    }

    public function getConceptEditRouteName(): string
    {
        return sprintf(
            'app_admin_dossier_%s_%s_concept',
            $this->dossierType->getValueForRouteName(),
            $this->getName()->value,
        );
    }

    public function getEditRouteName(): string
    {
        return sprintf(
            'app_admin_dossier_%s_%s_edit',
            $this->dossierType->getValueForRouteName(),
            $this->getName()->value,
        );
    }

    public static function create(DossierTypeConfigInterface $config, StepName $stepName): static
    {
        return new static($stepName, $config->getDossierType());
    }
}
