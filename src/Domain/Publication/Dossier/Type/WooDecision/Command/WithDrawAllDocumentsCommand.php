<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\WithdrawReason;
use Symfony\Component\Form\FormInterface;

readonly class WithDrawAllDocumentsCommand
{
    public function __construct(
        public WooDecision $dossier,
        public WithdrawReason $reason,
        public string $explanation,
    ) {
    }

    public static function fromForm(WooDecision $dossier, FormInterface $form): self
    {
        /** @var WithdrawReason $reason */
        $reason = $form->get('reason')->getData();

        /** @var string $explanation */
        $explanation = $form->get('explanation')->getData();

        return new self(
            $dossier,
            $reason,
            $explanation,
        );
    }
}
