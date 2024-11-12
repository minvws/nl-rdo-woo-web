<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Entity\EntityWithFileInfo;
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
