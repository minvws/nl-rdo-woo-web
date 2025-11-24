<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload\Type;

use Doctrine\ORM\QueryBuilder;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('domain.public.batchdownload.type')]
interface BatchDownloadTypeInterface
{
    public function supports(BatchDownloadScope $scope): bool;

    public function getFileBaseName(BatchDownloadScope $scope): string;

    public function getDocumentsQuery(BatchDownloadScope $scope): QueryBuilder;

    public function isAvailableForBatchDownload(BatchDownloadScope $scope): bool;
}
