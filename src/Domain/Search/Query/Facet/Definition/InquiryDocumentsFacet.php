<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Definition;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use App\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use App\Service\Search\Model\FacetKey;

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
