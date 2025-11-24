<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Subject\Subject as SubjectEntity;
use Shared\Service\Search\Model\FacetKey;
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
