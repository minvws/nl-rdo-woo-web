<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Type;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('domain.public.batchdownload.type')]
interface BatchDownloadTypeInterface
{
    public function supports(BatchDownloadScope $scope): bool;

    public function getFileBaseName(BatchDownloadScope $scope): string;

    public function getDocumentsQuery(BatchDownloadScope $scope): QueryBuilder;

    public function isAvailableForBatchDownload(BatchDownloadScope $scope): bool;
}
