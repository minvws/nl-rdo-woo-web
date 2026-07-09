<?php

declare(strict_types=1);

namespace PublicationApi\FeatureFlag;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class DossierUpdateGuard
{
    public function __construct(
        #[Autowire(param: 'enable_update_published_dossier_via_api')]
        private bool $enableUpdatePublishedDossierViaApi = false,
    ) {
    }

    public function assertDossierIsEditable(AbstractDossier $dossier): void
    {
        if ($this->enableUpdatePublishedDossierViaApi) {
            return;
        }

        if (! $dossier->getStatus()->isNewOrConcept()) {
            throw new ValidationException(
                ConstraintViolationList::createFromMessage('dossier update is not allowed in non-concept state'),
            );
        }
    }
}
