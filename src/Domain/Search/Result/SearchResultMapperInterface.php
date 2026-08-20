<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result;

use MinVWS\TypeArray\TypeArray;
use Shared\ApplicationId;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.search.result_mapper')]
interface SearchResultMapperInterface
{
    public function supports(ElasticDocumentType $type): bool;

    public function map(TypeArray $hit, ApplicationId $applicationId = ApplicationId::PUBLIC): ?ResultEntryInterface;
}
