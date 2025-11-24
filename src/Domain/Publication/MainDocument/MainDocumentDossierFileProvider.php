<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

readonly class MainDocumentDossierFileProvider implements DossierFileProviderInterface
{
    public function __construct(
        private MainDocumentRepository $mainDocumentRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getType(): DossierFileType
    {
        return DossierFileType::MAIN_DOCUMENT;
    }

    public function getEntityForPublicUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        $mainDocument = $this->mainDocumentRepository->findOneOrNullForDossier($dossier->getId(), Uuid::fromString($id));
        if ($mainDocument === null) {
            throw DossierFileNotFoundException::forEntity($this->getType(), $dossier, $id);
        }

        return $mainDocument;
    }

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo
    {
        // No additional checks needed
        return $this->getEntityForPublicUse($dossier, $id);
    }
}
