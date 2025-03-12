<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Subject\Subject as SubjectEntity;
use App\Service\Search\Model\FacetKey;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SubjectViewFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function make(SubjectEntity $subject): Subject
    {
        return new Subject(
            name: $subject->getName(),
            searchUrl: $this->urlGenerator->generate(
                'app_search',
                [
                    FacetKey::SUBJECT->getParamName() => [$subject->getName()],
                ],
            ),
        );
    }

    public function getSubjectForDossier(AbstractDossier $dossier): ?Subject
    {
        if ($dossier->getSubject() === null) {
            return null;
        }

        return $this->make($dossier->getSubject());
    }
}
