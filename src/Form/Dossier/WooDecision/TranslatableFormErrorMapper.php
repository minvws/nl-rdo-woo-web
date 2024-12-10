<?php

declare(strict_types=1);

namespace App\Form\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\ProductionReportProcessRun;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TranslatableFormErrorMapper
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function mapRunErrorsToForm(ProductionReportProcessRun $run, FormInterface $form): void
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
                    'publication.dossier.error.line_number',
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
