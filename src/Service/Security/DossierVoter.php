<?php

declare(strict_types=1);

namespace Shared\Service\Security;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
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
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $subject->getStatus()->isPublished();
    }
}
