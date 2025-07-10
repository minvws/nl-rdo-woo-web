<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Department\Department;

readonly class DepartmentFieldMapper
{
    private function __construct(
        private ?string $abbreviation,
        private string $value,
    ) {
    }

    public function getIndexValue(): string
    {
        return $this->abbreviation ? $this->abbreviation . '|' . $this->value : $this->value;
    }

    public static function fromString(string $rawValue): self
    {
        $rawValue = trim($rawValue);

        $separatorPosition = strpos($rawValue, '|');
        if ($separatorPosition === false) {
            return new self(null, $rawValue);
        }

        return new self(
            substr($rawValue, 0, $separatorPosition),
            substr($rawValue, $separatorPosition + 1),
        );
    }

    public static function fromDepartment(Department $department): self
    {
        return new self(
            $department->getShortTag(),
            $department->getName(),
        );
    }

    public function getValue(): string
    {
        return $this->abbreviation ?? $this->value;
    }

    public function getDescription(): string
    {
        return $this->value;
    }
}
