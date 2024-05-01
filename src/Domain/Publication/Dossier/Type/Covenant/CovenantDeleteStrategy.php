<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierDeleteHelper;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use Webmozart\Assert\Assert;

readonly class CovenantDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private DossierDeleteHelper $dossierDeleteHelper,
    ) {
    }

    public function delete(AbstractDossier $dossier): void
    {
        Assert::isInstanceOf($dossier, Covenant::class);

        $this->dossierDeleteHelper->deleteFileForEntity($dossier->getDocument());
        $this->dossierDeleteHelper->deleteAttachments($dossier->getAttachments());
    }
}
