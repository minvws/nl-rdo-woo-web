<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\SubType\Mapper;

use Shared\Domain\Search\Index\ElasticDocument;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.search.index.subtype_mapper')]
interface ElasticSubTypeMapperInterface
{
    public function supports(object $entity): bool;

    /**
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     */
    public function map(object $entity, ?array $metadata = null, ?array $pages = null): ElasticDocument;
}
