<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Definition;

use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use Shared\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use Shared\Service\Search\Model\FacetKey;

readonly class InquiryDocumentsFacet extends AbstractInquiryFacet
{
    public function getKey(): FacetKey
    {
        return FacetKey::INQUIRY_DOCUMENTS;
    }

    public function getField(): ElasticField
    {
        return ElasticField::INQUIRY_IDS;
    }

    public function getRequestParameter(): string
    {
        return 'dci';
    }

    public function getTitle(int|string $key, string $value): FacetDisplayValueInterface
    {
        return TranslatedFacetDisplayValue::fromString(
            'admin.dossiers.documents.inquiry_number',
        );
    }
}
