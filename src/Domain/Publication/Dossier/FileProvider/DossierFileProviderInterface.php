<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\FileProvider;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.publication.dossier.file_provider')]
interface DossierFileProviderInterface
{
    public function getType(): DossierFileType;

    public function getEntityForPublicUse(AbstractDossier $dossier, string $id): EntityWithFileInfo;

    public function getEntityForAdminUse(AbstractDossier $dossier, string $id): EntityWithFileInfo;
}
