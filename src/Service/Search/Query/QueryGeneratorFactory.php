<?php

declare(strict_types=1);

namespace App\Service\Search\Query;

use App\Service\Search\Model\Config;

class QueryGeneratorFactory
{
    public function __construct(
        protected DocumentQueryGenerator $docQueryGen,
        protected DossierQueryGenerator $dosQueryGen
    ) {
    }

    public function create(Config $config = new Config()): QueryGenerator
    {
        $generator = new QueryGenerator(
            $this->docQueryGen,
            $this->dosQueryGen,
            $config
        );

        return $generator;
    }
}
