<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Entity\EntityWithFileInfo;
use App\Service\Elastic\ElasticService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

readonly class DossierDeleteHelper
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private ElasticService $elasticService,
        private DocumentStorageService $storageService,
    ) {
    }

    public function deleteFromElasticSearch(AbstractDossier $dossier): void
    {
        $this->elasticService->removeDossier($dossier);
    }

    public function deleteFileForEntity(?EntityWithFileInfo $entity): void
    {
        if ($entity === null) {
            return;
        }

        $this->storageService->removeFileForEntity($entity);
    }

    public function delete(AbstractDossier $dossier): void
    {
        $this->doctrine->remove($dossier);
        $this->doctrine->flush();
    }

    /**
     * @param Collection<EntityWithFileInfo> $attachments
     */
    public function deleteAttachments(Collection $attachments): void
    {
        foreach ($attachments as $attachment) {
            $this->deleteFileForEntity($attachment);
        }
    }
}
