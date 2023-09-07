<?php

declare(strict_types=1);

namespace App\Exception;

class FixtureInventoryException extends \RuntimeException
{
    /**
     * @param array<string|int, array<int, string>> $errors
     */
    public function __construct(
        string $message,
        array $errors,
    ) {
        foreach ($errors as $error) {
            foreach ($error as $rowIndex => $errorDescription) {
                $message .= "\n- [$rowIndex] $errorDescription";
            }
        }

        parent::__construct($message);
    }

    /**
     * @param array<string|int, array<int, string>> $errors
     */
    public static function forProcessingErrors(array $errors): self
    {
        return new self('Errors during the processing of the inventory', $errors);
    }
}
