<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\DossierTypeSearchResultMapper;
use App\Domain\Search\Result\DossierTypeSearchResultMapperInterface;
use App\Domain\Search\Result\ResultEntryInterface;
use Jaytaph\TypeArray\TypeArray;

readonly class CovenantSearchResultMapper implements DossierTypeSearchResultMapperInterface
{
    public function __construct(
        private DossierTypeSearchResultMapper $baseMapper,
        private CovenantRepository $repository,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::COVENANT,
        );
    }
}
