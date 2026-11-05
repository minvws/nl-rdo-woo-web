<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Step;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Service\EnumHelper;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function count;

readonly class StepDefinition implements StepDefinitionInterface
{
    final public function __construct(
        private StepName $name,
        private DossierType $dossierType,
        private string $conceptRouteName,
        private string $editRouteName,
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
            EnumHelper::getStringValues(DossierValidationGroup::getValidationGroupsForStepName($this->name)),
        );

        return count($errors) === 0;
    }

    public function getConceptEditRouteName(): string
    {
        return $this->conceptRouteName;
    }

    public function getEditRouteName(): string
    {
        return $this->editRouteName;
    }

    public static function create(
        DossierTypeConfigInterface $config,
        StepName $stepName,
        string $conceptRouteName,
        string $editRouteName,
    ): static {
        return new static($stepName, $config->getDossierType(), $conceptRouteName, $editRouteName);
    }
}
