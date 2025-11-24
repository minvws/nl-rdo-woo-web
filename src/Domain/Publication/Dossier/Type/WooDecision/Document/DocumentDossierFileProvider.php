<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Security\DossierVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

readonly class DocumentDossierFileProvider implements DossierFileProviderInterface
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getType(): DossierFileType
    {
        return DossierFileType::DOCUMENT;
    }

    public function getEntityForPublicUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        if (! $dossier instanceof WooDecision) {
            throw DossierFileNotFoundException::forDossierTypeMismatch($this->getType(), $dossier);
        }

        $document = $this->documentRepository->findOneByDossierAndId($dossier, Uuid::fromString($id));
        if (
            $document === null
            || $document->shouldBeUploaded() === false
            || ! $this->authorizationChecker->isGranted(DossierVoter::VIEW, $document)
        ) {
            throw DossierFileNotFoundException::forEntity($this->getType(), $dossier, $id);
        }

        return $document;
    }

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        // No additional checks needed
        return $this->getEntityForPublicUse($dossier, $id);
    }
}
