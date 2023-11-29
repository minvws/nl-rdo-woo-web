<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\InventoryProcessRun;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatableFormErrorMapper
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function mapRunErrorsToForm(InventoryProcessRun $run, FormInterface $form): void
    {
        $this->mapGenericErrorsToForm($run->getGenericErrors(), $form);
        $this->mapRowErrorsToForm($run->getRowErrors(), $form);
    }

    /**
     * @param array<int, array<int, array{message: string, translation: string, placeholders: array<string, string>}>> $rowErrors
     */
    public function mapRowErrorsToForm(array $rowErrors, FormInterface $form): void
    {
        foreach ($rowErrors as $lineNumber => $errors) {
            foreach ($errors as $error) {
                // The placeholders themselves can be translation keys too
                $placeholders = array_map(
                    fn ($message) => $this->translator->trans($message),
                    $error['placeholders'],
                );

                $translatedError = $this->translator->trans($error['translation'], $placeholders);
                $errorMessage = $this->translator->trans(
                    'Line {line_number} error: {error}',
                    [
                        '{error}' => $translatedError,
                        '{line_number}' => $lineNumber,
                    ]
                );

                $form->addError(new FormError($errorMessage));
            }
        }
    }

    /**
     * @param array<int, array{message: string, translation: string, placeholders: array<string, string>}> $errors
     */
    public function mapGenericErrorsToForm(array $errors, FormInterface $form): void
    {
        foreach ($errors as $error) {
            // The placeholders themselves can be translation keys too
            $placeholders = array_map(
                fn ($message) => $this->translator->trans($message),
                $error['placeholders'],
            );

            $errorMessage = $this->translator->trans($error['translation'], $placeholders);

            $form->addError(new FormError($errorMessage));
        }
    }
}
