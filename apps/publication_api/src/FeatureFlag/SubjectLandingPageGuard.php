<?php

declare(strict_types=1);

namespace PublicationApi\FeatureFlag;

use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class SubjectLandingPageGuard
{
    public function __construct(
        #[Autowire(env: 'default::bool:HAS_FEATURE_SUBJECT_LANDING_PAGE')]
        private ?bool $hasFeatureSubjectLandingPage = null,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->hasFeatureSubjectLandingPage === true;
    }

    public function assertEnabled(): void
    {
        if ($this->isEnabled()) {
            return;
        }

        throw new ValidationException(
            ConstraintViolationList::createFromMessage('subject landing page feature is disabled'),
        );
    }
}
