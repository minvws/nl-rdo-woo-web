<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\InventoryProcessRun;

class FixtureInventoryException extends \RuntimeException
{
    /**
     * @param string[] $errors
     */
    public function __construct(string $message, array $errors)
    {
        foreach ($errors as $errorMessage) {
            $message .= "\n- $errorMessage";
        }

        parent::__construct($message);
    }

    public static function forProcessingErrors(InventoryProcessRun $run): self
    {
        $errors = [];
        foreach ($run->getGenericErrors() as $error) {
            $errors[] = $error['message'];
        }

        foreach ($run->getRowErrors() as $rowNumber => $rowErrors) {
            foreach ($rowErrors as $error) {
                $errors[] = "[$rowNumber] " . $error['message'];
            }
        }

        return new self('Errors during the processing of the inventory', $errors);
    }
}
