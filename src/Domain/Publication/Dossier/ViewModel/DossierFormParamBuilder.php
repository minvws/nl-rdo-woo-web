<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Doctrine\Common\Collections\Collection;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Uuid;

readonly class DossierFormParamBuilder
{
    /**
     * @return array{
     *     options: array<array-key, array{value:Uuid, label:string}>,
     *     values: array<array-key, Uuid>,
     *     errors: array<array-key, string>,
     * }
     */
    public function getDepartmentsFieldParams(AbstractDossier $dossier, FormInterface $form): array
    {
        return [
            'options' => $this->getDepartmentsOptions($dossier),
            'values' => $this->getDepartmentsValues($form),
            'errors' => $this->getDepartmentsErrors($form),
        ];
    }

    /**
     * @return array<array-key, array{value:Uuid, label:string}>
     */
    private function getDepartmentsOptions(AbstractDossier $dossier): array
    {
        return $dossier->getOrganisation()->getDepartments()->map(
            static fn (Department $department) => [
                'value' => $department->getId(),
                'label' => $department->getName(),
            ],
        )->toArray();
    }

    /**
     * @return array<array-key, Uuid>
     */
    private function getDepartmentsValues(FormInterface $form): array
    {
        $departments = $form->get('departments')->getData();
        if (! $departments instanceof Collection) {
            return [];
        }
        /** @var Collection<array-key,Department> $departments */

        return $departments->map(
            static fn (Department $department): Uuid => $department->getId(),
        )->toArray();
    }

    /**
     * @return array<array-key, string>
     */
    private function getDepartmentsErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->get('departments')->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
