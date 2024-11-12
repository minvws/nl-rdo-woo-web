<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\FileProvider;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Entity\EntityWithFileInfo;

interface DossierFileProviderInterface
{
    public function getType(): DossierFileType;

    public function getEntityForPublicUse(AbstractDossier $dossier, string $id): EntityWithFileInfo;

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo;
}
