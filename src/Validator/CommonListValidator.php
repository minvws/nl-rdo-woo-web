<?php

declare(strict_types=1);

namespace Shared\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function file;
use function in_array;
use function is_array;
use function is_null;
use function levenshtein;

use const FILE_IGNORE_NEW_LINES;

/**
 * This validator validates the password against a list of common passwords. If it matches or fuzzy matches, it fails.
 */
class CommonListValidator extends ConstraintValidator
{
    /** @var array<int,string>|null */
    protected ?array $commonList = null;

    /**
     * @param string $value
     */
    public function validate($value, Constraint $constraint): void
    {
        /** @var CommonList $constraint */
        if ($value === null || $value === '') {
            return;
        }

        if (is_null($this->commonList)) {
            $tmp = file(__DIR__ . '/../../config/upgraded-common-passwords.txt', FILE_IGNORE_NEW_LINES);
            if (is_array($tmp)) {
                $this->commonList = $tmp;
            }
        }

        if (in_array($value, $this->commonList ?? [])) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();

            return;
        }

        // Check to see if it sounds like a common password
        foreach ($this->commonList ?? [] as $entry) {
            if (levenshtein($value, $entry) < 3) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();

                return;
            }
        }
    }
}
