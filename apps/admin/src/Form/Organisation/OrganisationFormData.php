<?php

declare(strict_types=1);

namespace Admin\Form\Organisation;

use Admin\Validator\Organisation\UniqueOrganisation;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

use function array_values;

#[UniqueOrganisation]
final class OrganisationFormData
{
    /**
     * @param list<Department> $departments
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public string $name = '',
        #[Assert\NotNull]
        public ?OrganisationPrefix $prefix = null,
        #[Assert\Count(min: 1, minMessage: 'at_least_one_department_required')]
        public array $departments = [],
        public ?Uuid $organisationId = null,
    ) {
    }

    public static function fromEntity(Organisation $organisation): self
    {
        return new self(
            name: $organisation->getName(),
            prefix: $organisation->getPrefix(),
            departments: array_values($organisation->getDepartments()->toArray()),
            organisationId: $organisation->getId(),
        );
    }
}
