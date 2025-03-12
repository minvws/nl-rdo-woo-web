<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Security\DossierVoter;
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
