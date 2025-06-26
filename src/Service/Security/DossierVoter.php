<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Advice\Advice;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DossierVoter extends Voter
{
    public const VIEW = 'view';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::VIEW) {
            return false;
        }

        return match (true) {
            $subject instanceof Covenant => true,
            $subject instanceof InvestigationReport => true,
            $subject instanceof AnnualReport => true,
            $subject instanceof ComplaintJudgement => true,
            $subject instanceof Disposition => true,
            $subject instanceof OtherPublication => true,
            $subject instanceof Advice => true,
            $subject instanceof RequestForAdvice => true,
            default => false,
        };
    }

    /**
     * @param AbstractDossier $subject
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $subject->getStatus()->isPublished();
    }
}
