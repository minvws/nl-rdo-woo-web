<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.search.result_mapper')]
interface SearchResultMapperInterface
{
    public function supports(ElasticDocumentType $type): bool;

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface;
}
